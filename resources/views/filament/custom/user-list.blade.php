<div class="space-y-2">
    <table class="w-full text-left text-sm">
        <thead class="font-medium border-b">
            <tr>
                <th class="py-2">Name</th>
                <th class="py-2">Contact Number</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr class="border-b">
                    <td class="py-2">{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td class="py-2">{{ $user->contact_number }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="py-2 text-center text-gray-500">
                        No users found in this barangay.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>