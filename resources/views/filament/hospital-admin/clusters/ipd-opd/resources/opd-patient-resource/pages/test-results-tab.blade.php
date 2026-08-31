{{-- resources/views/filament/hospital-admin/clusters/ipd-opd/resources/opd-patient-resource/pages/test-results-tab.blade.php --}}
@php
    use Illuminate\Support\Facades\Storage;
    
    $state = $getState() ?? [];
    $recordId = $state['record_id'] ?? null;
    $caseableType = $state['caseable_type'] ?? \App\Models\OpdPrescription::class;
    $lookups = $state['lookups'] ?? [];

    if (empty($lookups) && $recordId) {
        $lookups = [
            ['caseable_type' => $caseableType, 'caseable_id' => $recordId],
        ];
    }
    
    if (empty($lookups)) {
        echo '<div class="p-4 text-red-500">Error: Record not found.</div>';
        return;
    }

    $applyLookups = function ($query) use ($lookups) {
        $query->where(function ($outer) use ($lookups) {
            foreach ($lookups as $lookup) {
                $outer->orWhere(function ($inner) use ($lookup) {
                    $inner->where('caseable_type', $lookup['caseable_type'])
                        ->whereIn('caseable_id', (array) $lookup['caseable_id']);
                });
            }
        });
    };
    
    $pathologyTests = \App\Models\ConsultationPathologyTest::query()
        ->tap($applyLookups)
        ->with([
            'pathologyTest.parameterItems.pathologyParameter.pathologyUnit',
            'pathologyCategory'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    
    $radiologyTests = \App\Models\ConsultationRadiologyTest::query()
        ->tap($applyLookups)
        ->with([
            'radiologyTest',
            'radiologyCategory',
            'patient.user'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

    // Helper function to get download URL
    function getRadiologyDownloadUrl($testId)
    {
        // Create a simple download URL
        return url('/download/radiology-test/' . $testId);
    }
    
    function getPathologyDownloadUrl($testId)
    {
        // Create a simple download URL for pathology
        return url('/download/pathology-test/' . $testId);
    }
@endphp

{{-- Simple version without Alpine.js --}}
<div class="space-y-6">
    @if($pathologyTests->isEmpty() && $radiologyTests->isEmpty())
        <div class="p-8 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-center">
                
                <p class="mt-4 text-gray-500">No test results found for this OPD visit.</p>
            </div>
        </div>
    @else
        <!-- Pathology Results Section -->
        @if($pathologyTests->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900">Pathology Results</h3>
                <span class="ml-3 px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                    {{ $pathologyTests->count() }} test(s)
                </span>
            </div>
            
            <div class="space-y-4">
                @foreach($pathologyTests as $test)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        <!-- Test Header -->
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $test->test_name }}</h3>
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="font-medium">Category:</span>
                                            <span class="ml-1">{{ $test->pathologyCategory->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="font-medium">Created:</span>
                                            <span class="ml-1">{{ $test->created_at->format('d M, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $test->is_processed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $test->is_processed ? 'Completed' : 'Pending' }}
                                    </span>
                                    <span class="mt-2 text-sm text-gray-500">
                                        Status: {{ $test->is_processed ? 'Processed' : 'Awaiting Results' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Content -->
                        <div class="p-6">
                            @if($test->is_processed && $test->pathologyTest)
                                <!-- Test Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Test Information</h4>
                                        <div class="space-y-3">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">Test Type</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $test->pathologyTest->test_type ?? 'N/A' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Method</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $test->pathologyTest->method ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Report Days</p>
                                                <p class="text-sm font-medium text-gray-900">{{ $test->pathologyTest->report_days ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Parameters -->
                                    @if($test->pathologyTest->parameterItems && $test->pathologyTest->parameterItems->count() > 0)
                                    <div class="space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Test Results</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Parameter</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Result</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Reference Range</th>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Unit</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($test->pathologyTest->parameterItems as $parameter)
                                                        <tr>
                                                            <td class="px-4 py-2">{{ $parameter->pathologyParameter->parameter_name ?? 'N/A' }}</td>
                                                            <td class="px-4 py-2 font-medium">{{ $parameter->patient_result }}</td>
                                                            <td class="px-4 py-2">{{ $parameter->pathologyParameter->reference_range }}</td>
                                                            <td class="px-4 py-2">{{ $parameter->pathologyParameter->pathologyUnit->name ?? 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- Notes -->
                                @if($test->pathologyTest->notes)
                                    <div class="mt-8 pt-8 border-t border-gray-200">
                                        <h4 class="text-lg font-medium text-gray-900 mb-2">Notes</h4>
                                        <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-200">{{ $test->pathologyTest->notes }}</p>
                                    </div>
                                @endif
                            @else
                                <!-- Pending Test -->
                                <div class="text-center py-8">
                                    <svg class="h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-4 text-gray-500">Test results are pending.</p>
                                    <p class="text-sm text-gray-400">The pathology lab is processing this test.</p>
                                </div>
                            @endif
                            
                            
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Radiology Results Section -->
        @if($radiologyTests->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-purple-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900">Radiology Results</h3>
                <span class="ml-3 px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                    {{ $radiologyTests->count() }} test(s)
                </span>
            </div>
            
            <div class="space-y-4">
                @foreach($radiologyTests as $test)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        <!-- Test Header -->
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $test->test_name }}</h3>
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="font-medium">Patient:</span>
                                            <span class="ml-1">{{ $test->patient->user->full_name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span class="font-medium">Category:</span>
                                            <span class="ml-1">{{ $test->radiologyCategory->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $test->is_processed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ isset($test->radiologyTest->uploaded_at) ? 'Completed' : 'Pending' }}
                                    </span>
                                    <span class="mt-2 text-sm text-gray-500">
                                        Created: {{ $test->created_at->format('d M, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Content -->
                        <div class="p-6">
                            @if(isset($test->radiologyTest->uploaded_at))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Basic Information -->
                                    <div class="space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Test Information</h4>
                                        <div class="space-y-3">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">Test Type</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $test->radiologyTest->test_type ?? 'N/A' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Short Name</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $test->radiologyTest->short_name ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Subcategory</p>
                                                <p class="text-sm font-medium text-gray-900">{{ $test->radiologyTest->subcategory ?? 'N/A' }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">Report Days</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $test->radiologyTest->report_days ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Documents Section -->
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <h4 class="text-lg font-medium text-gray-900">Test Results Document</h4>
                                            @if($test->radiologyTest->result_document_name)
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    Document Available
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($test->radiologyTest->result_document_name && $test->radiologyTest->document_path)
                                            <div class="space-y-3">
                                                <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                    <div class="flex items-center">
                                                        @if(str_ends_with(strtolower($test->radiologyTest->result_document_name), '.pdf'))
                                                            <svg class="h-8 w-8 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        @elseif(str_ends_with(strtolower($test->radiologyTest->result_document_name), '.doc') || str_ends_with(strtolower($test->radiologyTest->result_document_name), '.docx'))
                                                            <svg class="h-8 w-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-8 w-8 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        @endif
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900">{{ $test->radiologyTest->result_document_name }}</p>
                                                            <p class="text-xs text-gray-500">
                                                                Uploaded: {{ $test->radiologyTest->uploaded_at ? $test->radiologyTest->uploaded_at->format('d M, Y') : 'N/A' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <!-- Download Button Inside Document Box -->
                                                    <div class="ml-auto">
                                                        @php
                                                            $fileExists = Storage::exists($test->radiologyTest->document_path);
                                                            $downloadUrl = $fileExists ?Storage::url($test->radiologyTest->document_path) : '#';
                                                        @endphp
                                                        <a href="{{ $downloadUrl }}" 
                                                           @if($fileExists) target="_blank" @endif
                                                           class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white {{ $fileExists ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                            <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                                <svg class="h-12 w-12 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="mt-2 text-gray-500">No document uploaded yet.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Radiology Findings and Conclusion -->
                                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Findings -->
                                    @if($test->radiologyTest->findings)
                                        <div class="space-y-4">
                                            <h4 class="text-lg font-medium text-gray-900">Radiology Findings</h4>
                                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                <p class="text-gray-700 whitespace-pre-line">{{ $test->radiologyTest->findings }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Conclusion -->
                                    @if($test->radiologyTest->conclusion)
                                        <div class="space-y-4">
                                            <h4 class="text-lg font-medium text-gray-900">Conclusion</h4>
                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                                <p class="text-gray-700 whitespace-pre-line">{{ $test->radiologyTest->conclusion }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Impressions -->
                                @if($test->radiologyTest->impressions)
                                    <div class="mt-8 space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Impressions</h4>
                                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <p class="text-gray-700 whitespace-pre-line">{{ $test->radiologyTest->impressions }}</p>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Notes -->
                                @if($test->radiologyTest->notes)
                                    <div class="mt-8 space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Notes</h4>
                                        <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-200 whitespace-pre-line">{{ $test->radiologyTest->notes }}</p>
                                    </div>
                                @endif
                            @else
                                <!-- Pending Test -->
                                <div class="text-center py-8">
                                    <svg class="h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-4 text-gray-500">Radiology results are pending.</p>
                                    <p class="text-sm text-gray-400">The radiology department is processing this test.</p>
                                </div>
                            @endif
                            
                            <!-- Actions -->
                            @if(isset($test->radiologyTest->document_path))
                                <div class="mt-8 pt-8 border-t border-gray-200 flex justify-end space-x-2">
                                    <!-- Download Button -->
                                    @php
                                        $fileExists = Storage::exists($test->radiologyTest->document_path);
                                        $downloadUrl = $fileExists ? Storage::url($test->radiologyTest->document_path) : '#';

                                    @endphp
                                    
                                    @if($fileExists)
                                        <a href="{{ $downloadUrl }}" 
                                           target="_blank"
                                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="mr-2 h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Download Report
                                        </a>
                                    @endif
                                    

                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @endif
</div>