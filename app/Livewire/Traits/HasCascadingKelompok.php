<?php

namespace App\Livewire\Traits;

use App\Models\kelompok;

trait HasCascadingKelompok
{
    public function updated($property): void
    {
        $desaProps = ['desaId', 'desa_id', 'filterDesa', 'editDesa'];

        if (in_array($property, $desaProps)) {
            $this->resetKelompokOnDesaChange();
        }
    }

    protected function resetKelompokOnDesaChange(): void
    {
        foreach (['kelompokId', 'kelompok_id', 'filterKelompok', 'editKelompok'] as $prop) {
            if (property_exists($this, $prop)) {
                $this->$prop = is_string($this->$prop) ? '' : null;
            }
        }

        foreach (['daftarKelompok', 'daftarkelompok', 'kelompoks'] as $prop) {
            if (property_exists($this, $prop)) {
                $this->loadKelompokByDesa($prop);
                break;
            }
        }
    }

    protected function resolveDesaId(): mixed
    {
        return $this->desaId
            ?? $this->desa_id
            ?? $this->filterDesa
            ?? $this->editDesa
            ?? null;
    }

    protected function loadKelompokByDesa(?string $targetProperty = null): void
    {
        $desaId = $this->resolveDesaId();

        $query = kelompok::orderBy('kelompok_asal');

        if ($desaId) {
            $query->where('desa_id', $desaId);
        }

        $kelompoks = $query->get();

        if ($targetProperty && property_exists($this, $targetProperty)) {
            $this->$targetProperty = $kelompoks;
        }
    }
}
