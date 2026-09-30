<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\WebmasterSection;
use Auth;
use Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuthorsController extends Controller
{
    private string $uploadPath = 'authors';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorizeFeature();

        $GeneralWebmasterSections = $this->generalSections();
        $Authors = $this->visibleAuthors()
            ->withCount('topics')
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('q'));
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('position_ar', 'like', "%{$search}%")
                        ->orWhere('position_en', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(config('smartend.backend_pagination'))
            ->withQueryString();

        return view('dashboard.authors.list', compact('Authors', 'GeneralWebmasterSections'));
    }

    public function create()
    {
        $this->authorizeFeature('add_status');

        return view('dashboard.authors.create', [
            'GeneralWebmasterSections' => $this->generalSections(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeFeature('add_status');
        $validated = $this->validateAuthor($request);

        $Author = new Author;
        $this->fillAndSave($Author, $validated, $request);

        return redirect()->route('authors')->with('doneMessage', __('backend.addDone'));
    }

    public function edit($id)
    {
        $this->authorizeFeature('edit_status');
        $Author = $this->findVisibleAuthor($id);

        return view('dashboard.authors.edit', [
            'Author' => $Author,
            'GeneralWebmasterSections' => $this->generalSections(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeFeature('edit_status');
        $Author = $this->findVisibleAuthor($id);
        $validated = $this->validateAuthor($request, true);
        $this->fillAndSave($Author, $validated, $request);

        return redirect()->route('authors')->with('doneMessage', __('backend.saveDone'));
    }

    public function destroy($id)
    {
        $this->authorizeFeature('delete_status');
        $Author = $this->findVisibleAuthor($id);
        $this->deleteAuthor($Author);

        return redirect()->route('authors')->with('doneMessage', __('backend.deleteDone'));
    }

    public function updateAll(Request $request)
    {
        $this->authorizeFeature('edit_status');
        $validated = $request->validate([
            'action' => 'required|in:activate,block,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
        ]);

        $Authors = $this->visibleAuthors()->whereIn('id', $validated['ids'])->get();
        if ($validated['action'] === 'delete') {
            $this->authorizeFeature('delete_status');
            foreach ($Authors as $Author) {
                $this->deleteAuthor($Author);
            }

            return back()->with('doneMessage', __('backend.deleteDone'));
        }

        $status = $validated['action'] === 'activate' ? 1 : 0;
        Author::whereIn('id', $Authors->pluck('id'))->update([
            'status' => $status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return back()->with('doneMessage', __('backend.saveDone'));
    }

    private function validateAuthor(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name_ar' => 'required|string|max:191',
            'name_en' => 'nullable|string|max:191',
            'position_ar' => 'nullable|string|max:191',
            'position_en' => 'nullable|string|max:191',
            'photo' => 'nullable|image|mimes:'.config('filesystems.allowed_image_types').'|max:5120',
            'photo_delete' => $updating ? 'nullable|boolean' : 'prohibited',
            'status' => 'required|boolean',
        ], [], [
            'name_ar' => __('backend.authorArabicName'),
            'name_en' => __('backend.authorEnglishName'),
            'position_ar' => __('backend.authorArabicPosition'),
            'position_en' => __('backend.authorEnglishPosition'),
            'photo' => __('backend.authorPhoto'),
            'status' => __('backend.status'),
        ]);
    }

    private function fillAndSave(Author $Author, array $validated, Request $request): void
    {
        $Author->name_ar = trim(strip_tags($validated['name_ar']));
        $Author->name_en = $this->nullableText($validated['name_en'] ?? null);
        $Author->position_ar = $this->nullableText($validated['position_ar'] ?? null);
        $Author->position_en = $this->nullableText($validated['position_en'] ?? null);
        $Author->status = (int) $validated['status'];
        $Author->updated_by = Auth::id();
        if (!$Author->exists) {
            $Author->created_by = Auth::id();
        }

        if ($request->boolean('photo_delete') && $Author->photo) {
            FileHelper::deleteFile($this->uploadPath.'/'.$Author->photo);
            $Author->photo = null;
        }

        if ($request->hasFile('photo')) {
            if ($Author->photo) {
                FileHelper::deleteFile($this->uploadPath.'/'.$Author->photo);
            }
            $fileInfo = FileHelper::uploadFile($request->file('photo'), $this->uploadPath, 1);
            $Author->photo = $fileInfo['name'] ?? null;
        }

        $Author->save();
    }

    private function deleteAuthor(Author $Author): void
    {
        if ($Author->photo) {
            FileHelper::deleteFile($this->uploadPath.'/'.$Author->photo);
        }
        $Author->topics()->detach();
        $Author->delete();
    }

    private function visibleAuthors(): Builder
    {
        $query = Author::query();
        if (@Auth::user()->permissionsGroup->view_status) {
            $query->where('created_by', Auth::id());
        }

        return $query;
    }

    private function findVisibleAuthor($id): Author
    {
        return $this->visibleAuthors()->findOrFail($id);
    }

    private function authorizeFeature(?string $permission = null): void
    {
        $allowed = Helper::GeneralWebmasterSettings('authors_status')
            && @Auth::user()->permissionsGroup->authors_status;
        if ($permission) {
            $allowed = $allowed && @Auth::user()->permissionsGroup->{$permission};
        }
        abort_unless($allowed, 403);
    }

    private function generalSections()
    {
        return WebmasterSection::where('status', 1)->orderBy('row_no')->get();
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value !== '' ? $value : null;
    }
}
