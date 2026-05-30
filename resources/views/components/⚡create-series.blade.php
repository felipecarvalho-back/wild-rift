<?php

use Livewire\Component;
use App\Models\Series;
use App\Models\GameMatch;

new class extends Component
{
    public $title = '';
    public $type = 'bo3';
    public $team_a_name = '';
    public $team_b_name = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'type' => 'required|in:bo3,bo5',
        'team_a_name' => 'required|string|max:100',
        'team_b_name' => 'required|string|max:100',
    ];

    public function createSeries()
    {
        $this->validate();

        $series = Series::create([
            'title' => $this->title,
            'type' => $this->type,
            'team_a_name' => $this->team_a_name,
            'team_b_name' => $this->team_b_name,
            'status' => 'drafting',
        ]);

        $count = $this->type === 'bo3' ? 3 : 5;
        for ($i = 0; $i < $count; $i++) {
            GameMatch::create([
                'series_id' => $series->id,
                'match_number' => $i + 1,
                'blue_bans' => [],
                'red_bans' => [],
                'blue_picks' => [],
                'red_picks' => [],
                'priorities_selected' => [],
                'current_turn_index' => 0,
                'status' => 'drafting',
            ]);
        }

        return redirect()->route('series.draft', ['series' => $series->id]);
    }
};
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white flex items-center transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Voltar ao Dashboard
        </a>
    </div>

    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500 mb-2">Wild Rift Draft</h1>
        <p class="text-gray-400">Crie uma nova série competitiva</p>
    </header>

    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 shadow-xl">
        <form wire:submit.prevent="createSeries" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Título da Série</label>
                <input type="text" wire:model="title" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500" placeholder="Ex: Grande Final Campeonato">
                @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Formato</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" wire:model="type" value="bo3" class="form-radio text-blue-600 bg-gray-800 border-gray-700">
                        <span class="ml-2 text-white">MD3 (Melhor de 3)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" wire:model="type" value="bo5" class="form-radio text-blue-600 bg-gray-800 border-gray-700">
                        <span class="ml-2 text-white">MD5 (Melhor de 5)</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-blue-400 mb-2">Equipe A</label>
                    <input type="text" wire:model="team_a_name" class="w-full bg-gray-800 border border-blue-900 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500" placeholder="Ex: LOUD">
                    @error('team_a_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-red-400 mb-2">Equipe B</label>
                    <input type="text" wire:model="team_b_name" class="w-full bg-gray-800 border border-red-900 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-red-500" placeholder="Ex: RED Canids">
                    @error('team_b_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="pt-4 border-t border-gray-800">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                    Iniciar Draft
                </button>
            </div>
        </form>
    </div>
</div>