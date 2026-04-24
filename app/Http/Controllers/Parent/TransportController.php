<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\TransportLog;

class TransportController extends Controller
{
    use InteractsWithParentPortal;

    public function index()
    {
        $children = $this->ownedChildren(['classroom:id,name', 'transportAssignment.route.stops', 'transportAssignment.vehicle.driver']);
        $childIds = $children->pluck('id');

        $logs = TransportLog::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('student_id', $childIds)
            ->with(['student:id,full_name', 'route:id,route_name', 'vehicle:id,name,registration_number'])
            ->latest('logged_at')
            ->limit(20)
            ->get();

        return view('parent.transport.index', compact('children', 'logs'));
    }
}
