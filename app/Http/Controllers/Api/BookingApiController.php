<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Service;
use App\Services\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingApiController extends Controller
{
    public function __construct(private Availability $availability) {}

    /** Days in a month on which the service can be booked. */
    public function dates(Request $request): JsonResponse
    {
        $request->validate(['service' => 'required|integer', 'month' => 'required|date_format:Y-m']);
        $service = Service::query()->active()->findOrFail($request->integer('service'));

        $from = Carbon::createFromFormat('Y-m-d', $request->input('month').'-01')->startOfDay();
        $to = $from->copy()->endOfMonth();
        if ($from->lt(today())) {
            $from = today();
        }

        return response()->json(['dates' => $from->gt($to) ? (object) [] : (object) $this->availability->openDates($service, $this->staffId($request), $from, $to)]);
    }

    /** Bookable start times on one day. */
    public function slots(Request $request): JsonResponse
    {
        $request->validate(['service' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'ignore' => 'nullable|integer']);
        $service = Service::query()->active()->findOrFail($request->integer('service'));

        return response()->json([
            'slots' => $this->availability->slots($service, $this->staffId($request), Carbon::parse($request->input('date')), $request->integer('ignore') ?: null),
        ]);
    }

    public function promo(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:40']);
        $promo = PromoCode::lookup($request->input('code'));

        if (! $promo) {
            return response()->json(['message' => 'That code is not valid or has expired.'], 422);
        }

        return response()->json(['code' => $promo->code, 'percent' => $promo->percent, 'description' => $promo->description]);
    }

    private function staffId(Request $request): ?int
    {
        $staff = $request->input('staff');

        return ($staff === null || $staff === 'any' || $staff === '') ? null : (int) $staff;
    }
}
