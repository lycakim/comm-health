<div class="rounded-lg shadow-sm space-y-3">
    <h2 class="text-2xl font-bold text-gray-900">Patient Statistics</h2>
    
    <div class="rounded-md p-4 flex items-center justify-between border border-solid dark:border-gray-700">
        <span class="text-gray-500 text-sm">Total Patients</span>
        <span class="text-2xl font-bold">{{ $totalPatients }}</span>
    </div>
    
    <div class="space-y-2">
        <div class="flex flex-row justify-between gap-2">
            <div class="rounded-md p-4 border border-solid dark:border-gray-700 w-full">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Maternal</span>
                    <span class="text-xl font-bold">{{ $maternalCount }}</span>
                </div>
            </div>
            
            <div class="rounded-md p-4 border border-solid dark:border-gray-700 w-full">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Child</span>
                    <span class="text-xl font-bold">{{ $childCount }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-row justify-between gap-2">
            <div class="rounded-md p-4 border border-solid dark:border-gray-700 w-full">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Senior</span>
                    <span class="text-xl font-bold">{{ $seniorCount }}</span>
                </div>
            </div>
            
            <div class="rounded-md p-4 border border-solid dark:border-gray-700 w-full">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-sm">Chronic Conditions</span>
                    <span class="text-xl font-bold">{{ $chronicCount }}</span>
                </div>
            </div>
        </div>
    </div>
    
    {{-- <div>
        <h3 class="font-medium text-sm mb-3">Recent Activity</h3>
        <div class="space-y-2">
            @foreach($recentActivities as $activity)
                <div class="border-l-2 {{ $activity['type'] === 'add' ? 'border-green-500' : 'border-blue-500' }} pl-3">
                    <p class="font-medium">{{ $activity['description'] }}</p>
                    <p class="text-xs text-gray-500">Today, {{ $activity['time'] }}</p>
                </div>
            @endforeach
        </div>
    </div> --}}
</div>