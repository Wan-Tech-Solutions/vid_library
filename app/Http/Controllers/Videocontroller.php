<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Support\Str;
use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->status);
        }

        $videos = $query->latest()->paginate(10); // <-- Important: paginate, not get

        return view('videos.index', compact('videos'));
    }

    public function create()
    {
        return view('videos.create');
    }

    public function store(Request $request)
    {
        if ($request->hasFile('video')) {
            return $this->handleDirectUpload($request);
        }

        $request->validate([
            'upload_id' => 'required|string',
            'file_name' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_chunks' => 'required|integer|min:1',
        ]);

        $uploadId = $request->string('upload_id')->toString();
        $chunkDirectory = $this->getChunkDirectory($uploadId);

        if (! Storage::disk('local')->exists($chunkDirectory)) {
            return response()->json([
                'message' => 'Upload session not found. Please restart the upload.',
            ], 422);
        }

        $videoPath = $this->assembleChunks(
            $uploadId,
            $request->string('file_name')->toString(),
            $request->integer('total_chunks')
        );

        $thumbPath = $this->generateVideoThumbnail($videoPath);

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbPath,
            'uploaded_by' => auth()->id(),
            'is_published' => false,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($video)
            ->withProperties(['title' => $video->title])
            ->log('Video uploaded');

        return response()->json([
            'success' => true,
            'video_id' => $video->id,
        ], 201);
    }


    public function show(string $id)
    {
        $video = Video::findOrFail($id);
        return view('videos.show', compact('video'));
    }

    public function edit(string $id)
    {
        $video = Video::findOrFail($id);
        return view('videos.edit', compact('video'));
    }

    public function update(Request $request, $id)
    {
        $video = Video::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'nullable|mimes:mp4|max:512000',
        ]);

        $video->title = $request->title;
        $video->description = $request->description;

        // If a new video is uploaded
        if ($request->hasFile('video')) {
            if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
                Storage::disk('public')->delete($video->video_path);
            }

            if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }

            $newVideoPath = $request->file('video')->store('videos', 'public');
            $video->video_path = $newVideoPath;
            $video->thumbnail_path = $this->generateVideoThumbnail($newVideoPath);
        }

        $video->save();

        return redirect()->route('videos.index')->with('success', 'Video updated successfully.');
    }

    public function destroy(string $id)
    {
        $video = Video::findOrFail($id);
        Storage::disk('public')->delete($video->video_path);
        $video->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($video)
            ->withProperties(['title' => $video->title])
            ->log('Video deleted');

        return redirect()->route('videos.index')->with('success', 'Video deleted.');
    }

    public function togglePublish($id)
    {
        $video = Video::findOrFail($id);
        $video->is_published = !$video->is_published;
        $video->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($video)
            ->withProperties(['title' => $video->title])
            ->log('Video ' . ($video->is_published ? 'published' : 'unpublished'));

        return back()->with('success', 'Video ' . ($video->is_published ? 'published' : 'hidden') . ' successfully.');
    }

    public function reorder(Request $request)
    {
        foreach ($request->order as $videoData) {
            Video::where('id', $videoData['id'])->update(['order' => $videoData['position']]);
        }

        return response()->json(['success' => true]);
    }

    protected function generateVideoThumbnail($videoPath)
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($videoPath)) {
            return null;
        }

        $ffmpegBinary = (string) config('services.ffmpeg.ffmpeg');
        $ffprobeBinary = (string) config('services.ffmpeg.ffprobe');

        if ($ffmpegBinary === '' || $ffprobeBinary === '') {
            return null;
        }

        if (! is_file($ffmpegBinary) || ! is_file($ffprobeBinary)) {
            return null;
        }

        $videoFileName = basename($videoPath);
        $thumbnailName = pathinfo($videoFileName, PATHINFO_FILENAME) . '.jpg';
        $relativeThumbnailPath = 'thumbnails/' . $thumbnailName;

        $videoFullPath = $disk->path($videoPath);
        $thumbnailFullPath = $disk->path($relativeThumbnailPath);

        $disk->makeDirectory('thumbnails');

        try {
            $config = $this->ffmpegConfiguration();
            $ffmpeg = FFMpeg::create($config);
            $ffprobe = FFProbe::create($config);
        } catch (ExecutableNotFoundException $exception) {
            logger()->warning('FFMpeg binaries are missing. Skipping thumbnail generation.', [
                'path' => $videoPath,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        } catch (\Throwable $exception) {
            logger()->warning('Unable to initialise FFMpeg. Skipping thumbnail generation.', [
                'path' => $videoPath,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $video = $ffmpeg->open($videoFullPath);

        try {
            $duration = (int) ceil($ffprobe->format($videoFullPath)->get('duration') ?? 0);
        } catch (\Throwable $e) {
            $duration = 0;
        }

        $captureSecond = 1;

        if ($duration > 0) {
            $captureSecond = $duration > 15 ? 15 : max(1, $duration - 1);
        }

        try {
            $video->frame(TimeCode::fromSeconds($captureSecond))
                ->save($thumbnailFullPath);
        } catch (\Throwable $e) {
            $video->frame(TimeCode::fromSeconds(1))->save($thumbnailFullPath);
        }

        return $relativeThumbnailPath;
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file_name' => 'required|string',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->string('upload_id')->toString();
        $chunkIndex = $request->integer('chunk_index');

        $chunkDirectory = $this->getChunkDirectory($uploadId);
        Storage::disk('local')->makeDirectory($chunkDirectory);

        $chunkFileName = $this->formatChunkName($chunkIndex);
        Storage::disk('local')->putFileAs(
            $chunkDirectory,
            $request->file('chunk'),
            $chunkFileName
        );

        return response()->json([
            'success' => true,
            'received_chunk' => $chunkIndex,
            'next_chunk' => $chunkIndex + 1,
        ]);
    }

    public function cancelUpload(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->string('upload_id')->toString();
        $chunkDirectory = $this->getChunkDirectory($uploadId);

        if (Storage::disk('local')->exists($chunkDirectory)) {
            Storage::disk('local')->deleteDirectory($chunkDirectory);
        }

        return response()->json(['success' => true]);
    }

    public function uploadStatus(string $uploadId)
    {
        $chunkDirectory = $this->getChunkDirectory($uploadId);

        if (! Storage::disk('local')->exists($chunkDirectory)) {
            return response()->json([
                'uploaded_chunks' => [],
            ]);
        }

        $files = collect(Storage::disk('local')->files($chunkDirectory))
            ->map(fn ($path) => (int) basename($path))
            ->sort()
            ->values();

        return response()->json([
            'uploaded_chunks' => $files,
        ]);
    }

    protected function handleDirectUpload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'required|mimes:mp4|max:512000',
        ]);

        $videoPath = $request->file('video')->store('videos', 'public');

        $thumbPath = $this->generateVideoThumbnail($videoPath);

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbPath,
            'uploaded_by' => auth()->id(),
            'is_published' => false,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($video)
            ->withProperties(['title' => $video->title])
            ->log('Video uploaded');

        return response()->json(['success' => true], 200);
    }

    protected function assembleChunks(string $uploadId, string $originalName, int $expectedChunks): string
    {
        $chunkDirectory = $this->getChunkDirectory($uploadId);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension !== 'mp4') {
            throw new \InvalidArgumentException('Only MP4 uploads are supported.');
        }

        $finalFileName = (string) Str::uuid() . '.' . $extension;
        $finalPath = 'videos/' . $finalFileName;

        Storage::disk('public')->makeDirectory('videos');

        $destination = Storage::disk('public')->path($finalPath);
        $output = fopen($destination, 'ab');

        $chunkFiles = collect(Storage::disk('local')->files($chunkDirectory))
            ->sort()
            ->values();

        if ($chunkFiles->count() !== $expectedChunks) {
            fclose($output);
            throw new \RuntimeException('Uploaded chunks do not match the expected count.');
        }

        foreach ($chunkFiles as $chunkFile) {
            $chunkPath = Storage::disk('local')->path($chunkFile);
            $handle = fopen($chunkPath, 'rb');
            stream_copy_to_stream($handle, $output);
            fclose($handle);
        }

        fclose($output);

        Storage::disk('local')->deleteDirectory($chunkDirectory);

        return $finalPath;
    }

    protected function getChunkDirectory(string $uploadId): string
    {
        return 'chunks/' . $uploadId;
    }

    protected function formatChunkName(int $index): string
    {
        return str_pad((string) $index, 6, '0', STR_PAD_LEFT);
    }

    protected function ffmpegConfiguration(): array
    {
        $config = [];

        if ($ffmpegBinary = config('services.ffmpeg.ffmpeg')) {
            $config['ffmpeg.binaries'] = $ffmpegBinary;
        }

        if ($ffprobeBinary = config('services.ffmpeg.ffprobe')) {
            $config['ffprobe.binaries'] = $ffprobeBinary;
        }

        $timeout = config('services.ffmpeg.timeout');
        if (! is_null($timeout) && $timeout !== '') {
            $config['timeout'] = (int) $timeout;
        }

        $threads = config('services.ffmpeg.threads');
        if (! is_null($threads) && $threads !== '') {
            $config['ffmpeg.threads'] = (int) $threads;
        }

        return $config;
    }
}
