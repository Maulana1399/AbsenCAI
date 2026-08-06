<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('DATA PERSON') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Master Data Person') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex items-center justify-between mb-6">
        <livewire:master-data.person.create-person />
        <livewire:master-data.person.import-person />
    </div>

    @livewire('master-data.person.index-person')
    <livewire:master-data.person.edit-person />
    <livewire:master-data.person.delete-person />
</x-layouts.app>
