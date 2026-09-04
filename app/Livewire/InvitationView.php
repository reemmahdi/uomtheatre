<?php

namespace App\Livewire;

use App\Models\Reservation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.invitation')]
#[Title('دعوة حضور - مسرح جامعة الموصل')]
class InvitationView extends Component
{
    public string $qrCode;
    public ?Reservation $reservation = null;
    public string $qrImage = '';
    public bool $notFound = false;
    public array $neighbors = [];

    public function mount(string $qrCode)
    {
        $this->qrCode = $qrCode;

        $this->reservation = Reservation::with(['event.status', 'seat.section', 'event.creator'])
            ->where('qr_code', $qrCode)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (!$this->reservation) {
            $this->notFound = true;
            return;
        }

        $this->qrImage = $this->generateQrCode($qrCode);

        $this->neighbors = $this->calculateNeighbors();
    }

    private function generateQrCode(string $data): string
    {
        try {
            $builder = new Builder(
                writer: new SvgWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 280,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            );

            $result = $builder->build();

            return base64_encode($result->getString());
        } catch (\Exception $e) {
            \Log::error('QR generation failed: ' . $e->getMessage());
            return '';
        }
    }

    private function calculateNeighbors(): array
    {
        if (!$this->reservation) return [];

        $seat = $this->reservation->seat;
        $eventId = $this->reservation->event_id;

        $directions = [
            ['col' => $seat->seat_number - 1, 'row' => $seat->row_number, 'label' => 'على اليمين',  'icon' => 'bi-arrow-right'],
            ['col' => $seat->seat_number + 1, 'row' => $seat->row_number, 'label' => 'على اليسار',  'icon' => 'bi-arrow-left'],
            ['col' => $seat->seat_number,     'row' => $seat->row_number - 1, 'label' => 'أمامكم',   'icon' => 'bi-arrow-up'],
            ['col' => $seat->seat_number,     'row' => $seat->row_number + 1, 'label' => 'خلفكم',    'icon' => 'bi-arrow-down'],
        ];

        $neighbors = [];
        foreach ($directions as $dir) {
            $neighbor = Reservation::with('seat')
                ->where('event_id', $eventId)
                ->where('status', 'confirmed')
                ->whereHas('seat', fn($q) => $q
                    ->where('section_id', $seat->section_id)
                    ->where('row_number', $dir['row'])
                    ->where('seat_number', $dir['col']))
                ->first();

            if ($neighbor) {
                $neighbors[] = [
                    'label' => $dir['label'],
                    'icon'  => $dir['icon'],
                    'name'  => explode(' ', trim($neighbor->guest_name ?? ''))[0] ?: 'ضيف',
                ];
            }
        }

        return $neighbors;
    }

    public function render()
    {
        return view('livewire.invitation-view');
    }
}
