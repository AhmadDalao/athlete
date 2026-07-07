<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactSubmissionExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $submissions = ContactSubmission::query()
            ->when(in_array($status, ['new', 'reviewed', 'closed'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($submissions): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'name', 'email', 'phone', 'message', 'status', 'created_at']);

            foreach ($submissions as $submission) {
                fputcsv($handle, [
                    $submission->id,
                    $submission->name,
                    $submission->email,
                    $submission->phone,
                    $submission->message,
                    $submission->status,
                    $submission->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-contact-submissions-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
