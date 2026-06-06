<?php

use Livewire\Component;
use App\Models\Series;
use App\Models\Champion;

new class extends Component
{
    public Series $series;
    public $championsMap = [];

    public function mount(Series $series)
    {
        $this->series = $series;
        $this->series->load('matches');
        $this->championsMap = Champion::select(['id', 'name', 'image_url'])->get()->keyBy('id')->toArray();
    }
};
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white inline-flex items-center transition font-semibold text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Voltar ao Menu Principal
        </a>
    </div>

    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold tracking-tight text-white mb-2">{{ $series->title }}</h1>
        <p class="text-gray-400">Resumo da Série - {{ strtoupper($series->type) }}</p>
        <div class="mt-6 flex items-center justify-center space-x-8 text-2xl font-bold">
            <span class="text-blue-500">{{ $series->team_a_name }}</span>
            <span class="text-gray-500 text-lg">VS</span>
            <span class="text-red-500">{{ $series->team_b_name }}</span>
        </div>
    </header>

    <div class="space-y-8">
        @foreach($series->matches as $index => $match)
            @php
                $teamABlue = $index % 2 === 0;
                $teamABans = $teamABlue ? $match->blue_bans : $match->red_bans;
                $teamAPicks = $teamABlue ? $match->blue_picks : $match->red_picks;
                $teamBBans = $teamABlue ? $match->red_bans : $match->blue_bans;
                $teamBPicks = $teamABlue ? $match->red_picks : $match->blue_picks;
            @endphp
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-gray-800 px-6 py-4 flex justify-between items-center border-b border-gray-700">
                    <h3 class="text-xl font-bold text-white">Jogo {{ $match->match_number }}</h3>
                    <span class="px-3 py-1 rounded text-sm {{ $match->status === 'completed' ? 'bg-green-900/50 text-green-400' : 'bg-yellow-900/50 text-yellow-400' }}">
                        {{ ucfirst($match->status) }}
                    </span>
                </div>
                
                <div class="grid grid-cols-2">
                    <!-- Equipe A -->
                    <div class="p-6 border-r border-gray-800 {{ $teamABlue ? 'bg-blue-900/10' : 'bg-red-900/10' }}">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-lg {{ $teamABlue ? 'text-blue-400' : 'text-red-400' }}">{{ $series->team_a_name }}</h4>
                            <span class="text-xs text-gray-500 uppercase">{{ $teamABlue ? 'Blue Side' : 'Red Side' }}</span>
                        </div>
                        
                        <div class="mb-4">
                            <div class="text-xs text-gray-500 mb-2 uppercase">Bans</div>
                            <div class="flex space-x-2">
                                @foreach($teamABans ?? [] as $banId)
                                    <div class="w-10 h-10 rounded border border-gray-700 overflow-hidden grayscale">
                                        <img src="{{ $championsMap[$banId]['image_url'] ?? '' }}" alt="Ban" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <div class="text-xs text-gray-500 mb-2 uppercase">Picks</div>
                            <div class="flex space-x-2">
                                @foreach($teamAPicks ?? [] as $pickId)
                                    <div class="w-12 h-12 rounded border {{ in_array($pickId, $match->priorities_selected ?? []) ? 'border-yellow-500' : 'border-gray-600' }} overflow-hidden relative">
                                        <img src="{{ $championsMap[$pickId]['image_url'] ?? '' }}" alt="Pick" class="w-full h-full object-cover">
                                        @if(in_array($pickId, $match->priorities_selected ?? []))
                                            <span class="absolute top-0 right-0 text-yellow-500"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Equipe B -->
                    <div class="p-6 {{ !$teamABlue ? 'bg-blue-900/10' : 'bg-red-900/10' }}">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-lg {{ !$teamABlue ? 'text-blue-400' : 'text-red-400' }}">{{ $series->team_b_name }}</h4>
                            <span class="text-xs text-gray-500 uppercase">{{ !$teamABlue ? 'Blue Side' : 'Red Side' }}</span>
                        </div>
                        
                        <div class="mb-4 flex flex-col items-end">
                            <div class="text-xs text-gray-500 mb-2 uppercase">Bans</div>
                            <div class="flex space-x-2">
                                @foreach($teamBBans ?? [] as $banId)
                                    <div class="w-10 h-10 rounded border border-gray-700 overflow-hidden grayscale">
                                        <img src="{{ $championsMap[$banId]['image_url'] ?? '' }}" alt="Ban" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-end">
                            <div class="text-xs text-gray-500 mb-2 uppercase">Picks</div>
                            <div class="flex space-x-2">
                                @foreach($teamBPicks ?? [] as $pickId)
                                    <div class="w-12 h-12 rounded border {{ in_array($pickId, $match->priorities_selected ?? []) ? 'border-yellow-500' : 'border-gray-600' }} overflow-hidden relative">
                                        <img src="{{ $championsMap[$pickId]['image_url'] ?? '' }}" alt="Pick" class="w-full h-full object-cover">
                                        @if(in_array($pickId, $match->priorities_selected ?? []))
                                            <span class="absolute top-0 right-0 text-yellow-500"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 text-center flex justify-center space-x-4">
        <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-white rounded-lg font-bold transition shadow-md">Menu Principal</a>
        <a href="{{ route('series.create') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition shadow-md">Criar Nova Série</a>
    </div>
</div>