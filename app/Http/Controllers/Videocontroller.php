<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class VideoController extends Controller
{
    protected array $allowedVideoExtensions = [
        'mp4',
        'mov',
        'm4v',
        'avi',
        'mkv',
        'webm',
        'mpg',
        'mpeg',
        'wmv',
        '3gp',
        '3g2',
    ];

    protected array $allowedVideoMimeTypes = [
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
        'video/webm',
        'video/mpeg',
        'video/x-ms-wmv',
        'video/3gpp',
        'video/3gpp2',
    ];

    protected string $unsupportedFormatMessage = 'Unsupported video format. Please upload MP4, MOV, AVI, MKV, or WEBM files.';

    public function index(Request $request)
    {
        $query = Video::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->status);
        }

        $videos = $query->latest()->paginate(10)->withQueryString();

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
            'file_name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! $this->isAllowedExtension($value)) {
                        $fail($this->unsupportedFormatMessage);
                    }
                },
            ],
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

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $videoPath,
            'thumbnail_path' => null,
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
            $video->thumbnail_path = null;
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

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file_name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! $this->isAllowedExtension($value)) {
                        $fail($this->unsupportedFormatMessage);
                    }
                },
            ],
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
            'video' => [
                'required',
                File::types($this->allowedVideoExtensions)
                    ->mimeTypes($this->allowedVideoMimeTypes)
                    ->max(512000),
            ],
        ]);

        $videoPath = $request->file('video')->store('videos', 'public');

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $videoPath,
            'thumbnail_path' => null,
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
        $extension = $this->extractExtension($originalName);

        if (! $this->isAllowedExtension($originalName)) {
            throw new \InvalidArgumentException($this->unsupportedFormatMessage);
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

    protected function extractExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    protected function isAllowedExtension(?string $fileName): bool
    {
        if ($fileName === null || trim($fileName) === '') {
            return false;
        }

        $extension = $this->extractExtension($fileName);

        return $extension !== '' && in_array($extension, $this->allowedVideoExtensions, true);
    }

    protected function getChunkDirectory(string $uploadId): string
    {
        return 'chunks/' . $uploadId;
    }

    protected function formatChunkName(int $index): string
    {
        return str_pad((string) $index, 6, '0', STR_PAD_LEFT);
    }

}
