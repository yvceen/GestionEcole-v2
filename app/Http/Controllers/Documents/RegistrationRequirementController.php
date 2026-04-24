<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequirementItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RegistrationRequirementController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->portalContext($request);
        $schoolId = $this->schoolId();

        $items = RegistrationRequirementItem::query()
            ->where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('documents.registration-requirements.index', [
            'context' => $context,
            'items' => $items,
            'groupedItems' => $items->groupBy(fn (RegistrationRequirementItem $item) => $item->category ?: 'Autres'),
            'categoryOptions' => $this->categoryOptions(),
            'school' => $this->currentSchool(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $context = $this->portalContext($request);
        $schoolId = $this->schoolId();
        $data = $this->validatedData($request);

        RegistrationRequirementItem::create([
            'school_id' => $schoolId,
            'category' => $data['category'],
            'label' => $data['label'],
            'notes' => $data['notes'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $this->nextSortOrder($schoolId),
        ]);

        return redirect()
            ->route($context['routes']['index'])
            ->with('success', 'Element ajoute a la liste des pieces d inscription.');
    }

    public function update(Request $request, RegistrationRequirementItem $item): RedirectResponse
    {
        $context = $this->portalContext($request);
        $schoolId = $this->schoolId();
        $this->guardItem($item, $schoolId);
        $data = $this->validatedData($request);

        $item->update([
            'category' => $data['category'],
            'label' => $data['label'],
            'notes' => $data['notes'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route($context['routes']['index'])
            ->with('success', 'Element mis a jour.');
    }

    public function destroy(Request $request, RegistrationRequirementItem $item): RedirectResponse
    {
        $context = $this->portalContext($request);
        $schoolId = $this->schoolId();
        $this->guardItem($item, $schoolId);

        $item->delete();

        return redirect()
            ->route($context['routes']['index'])
            ->with('success', 'Element supprime.');
    }

    public function move(Request $request, RegistrationRequirementItem $item, string $direction): RedirectResponse
    {
        $context = $this->portalContext($request);
        $schoolId = $this->schoolId();
        $this->guardItem($item, $schoolId);

        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $swap = RegistrationRequirementItem::query()
            ->where('school_id', $schoolId)
            ->when(
                $direction === 'up',
                fn ($query) => $query->where('sort_order', '<', (int) $item->sort_order)->orderByDesc('sort_order'),
                fn ($query) => $query->where('sort_order', '>', (int) $item->sort_order)->orderBy('sort_order')
            )
            ->first();

        if ($swap) {
            $currentOrder = (int) $item->sort_order;
            $item->update(['sort_order' => (int) $swap->sort_order]);
            $swap->update(['sort_order' => $currentOrder]);
        }

        return redirect()->route($context['routes']['index']);
    }

    public function preview(Request $request)
    {
        $context = $this->portalContext($request);
        $items = $this->activeGroupedItems();

        return view('documents.registration-requirements.preview', [
            'context' => $context,
            'school' => $this->currentSchool(),
            'groupedItems' => $items,
        ]);
    }

    public function pdf(Request $request)
    {
        $context = $this->portalContext($request);
        $school = $this->currentSchool();
        $fileName = 'pieces-inscription-' . ($school?->slug ?: 'myedu') . '.pdf';

        return Pdf::loadView('documents.registration-requirements.pdf', [
            'context' => $context,
            'school' => $school,
            'groupedItems' => $this->activeGroupedItems(),
        ])->setPaper('a4')->download($fileName);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function activeGroupedItems(): Collection
    {
        return RegistrationRequirementItem::query()
            ->where('school_id', $this->schoolId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (RegistrationRequirementItem $item) => $item->category ?: 'Autres');
    }

    private function guardItem(RegistrationRequirementItem $item, int $schoolId): void
    {
        abort_unless((int) $item->school_id === $schoolId, 404);
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function currentSchool()
    {
        return app()->bound('currentSchool')
            ? app('currentSchool')
            : (app()->bound('current_school') ? app('current_school') : null);
    }

    private function nextSortOrder(int $schoolId): int
    {
        return ((int) RegistrationRequirementItem::query()
            ->where('school_id', $schoolId)
            ->max('sort_order')) + 1;
    }

    private function categoryOptions(): array
    {
        return [
            'Documents de l eleve',
            'Documents du parent',
            'Photos',
            'Paiement / administratif',
            'Pieces medicales',
            'Autres',
        ];
    }

    private function portalContext(Request $request): array
    {
        $name = (string) ($request->route()?->getName() ?? '');

        if (str_starts_with($name, 'school-life.')) {
            return [
                'layout' => 'school-life-layout',
                'title' => 'Documents et impressions',
                'subtitle' => 'Liste d inscription et documents remettables aux familles.',
                'portal' => 'Vie scolaire',
                'routes' => [
                    'index' => 'school-life.documents.registration-requirements.index',
                    'store' => 'school-life.documents.registration-requirements.store',
                    'update' => 'school-life.documents.registration-requirements.update',
                    'destroy' => 'school-life.documents.registration-requirements.destroy',
                    'move' => 'school-life.documents.registration-requirements.move',
                    'preview' => 'school-life.documents.registration-requirements.preview',
                    'pdf' => 'school-life.documents.registration-requirements.pdf',
                ],
            ];
        }

        if (str_starts_with($name, 'director.')) {
            return [
                'layout' => 'director-layout',
                'title' => 'Documents et impressions',
                'subtitle' => 'Documents de direction, diffusion imprimable et generation PDF.',
                'portal' => 'Direction',
                'routes' => [
                    'index' => 'director.documents.registration-requirements.index',
                    'store' => 'director.documents.registration-requirements.store',
                    'update' => 'director.documents.registration-requirements.update',
                    'destroy' => 'director.documents.registration-requirements.destroy',
                    'move' => 'director.documents.registration-requirements.move',
                    'preview' => 'director.documents.registration-requirements.preview',
                    'pdf' => 'director.documents.registration-requirements.pdf',
                ],
            ];
        }

        return [
            'layout' => 'admin-layout',
            'title' => 'Documents et impressions',
            'subtitle' => 'Preparation des pieces d inscription et export PDF brandes.',
            'portal' => 'Administration',
            'routes' => [
                'index' => 'admin.documents.registration-requirements.index',
                'store' => 'admin.documents.registration-requirements.store',
                'update' => 'admin.documents.registration-requirements.update',
                'destroy' => 'admin.documents.registration-requirements.destroy',
                'move' => 'admin.documents.registration-requirements.move',
                'preview' => 'admin.documents.registration-requirements.preview',
                'pdf' => 'admin.documents.registration-requirements.pdf',
            ],
        ];
    }
}
