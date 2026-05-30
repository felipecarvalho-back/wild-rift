<?php

use Livewire\Component;
use App\Models\Champion;
use App\Models\Series;
use App\Models\GameMatch;

new class extends Component
{
    public Series $series;
    public $currentMatchIndex = 0;
    
    public $champions = [];
    public $search = '';
    public $selectedRole = '';

    const TURNS = [
        // Fase 1: 3 Bans, 3 Picks (1-2-2-1)
        ['team' => 'blue', 'action' => 'ban'],
        ['team' => 'red', 'action' => 'ban'],
        ['team' => 'blue', 'action' => 'ban'],
        ['team' => 'red', 'action' => 'ban'],
        ['team' => 'blue', 'action' => 'ban'],
        ['team' => 'red', 'action' => 'ban'],
        ['team' => 'blue', 'action' => 'pick'],
        ['team' => 'red', 'action' => 'pick'],
        ['team' => 'red', 'action' => 'pick'],
        ['team' => 'blue', 'action' => 'pick'],
        ['team' => 'blue', 'action' => 'pick'],
        ['team' => 'red', 'action' => 'pick'],
        // Fase 2: 2 Bans (Red inicia), 2 Picks (1-1-1-1)
        ['team' => 'red', 'action' => 'ban'],
        ['team' => 'blue', 'action' => 'ban'],
        ['team' => 'red', 'action' => 'ban'],
        ['team' => 'blue', 'action' => 'ban'],
        ['team' => 'red', 'action' => 'pick'],
        ['team' => 'blue', 'action' => 'pick'],
        ['team' => 'blue', 'action' => 'pick'],
        ['team' => 'red', 'action' => 'pick'],
    ];

    public function mount(Series $series)
    {
        $this->series = $series;
        $this->series->load('matches');
        $this->champions = Champion::orderBy('name')->get()->toArray();
    }

    public function selectRole($role)
    {
        $this->selectedRole = $role;
        $this->search = '';
    }

    public function getFilteredChampionsProperty()
    {
        $query = Champion::orderBy('is_priority', 'desc')->orderBy('name', 'asc');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->selectedRole) {
            $query->where(function($q) {
                $q->where('role', $this->selectedRole)
                  ->orWhere('secondary_role', 'like', '%' . $this->selectedRole . '%');
            });
        }

        return $query->get()->toArray();
    }

    public function getMatchesProperty()
    {
        return $this->series->matches;
    }

    public function getFearlessBlockedChampionsProperty()
    {
        $blocked = [];
        foreach ($this->matches as $index => $match) {
            if ($index < $this->currentMatchIndex) {
                $blocked = array_merge($blocked, $match->blue_picks ?? [], $match->red_picks ?? []);
            }
        }
        return array_unique($blocked);
    }

    public function getBlueTeamNameProperty()
    {
        return $this->currentMatchIndex % 2 === 0 ? $this->series->team_a_name : $this->series->team_b_name;
    }

    public function getRedTeamNameProperty()
    {
        return $this->currentMatchIndex % 2 === 0 ? $this->series->team_b_name : $this->series->team_a_name;
    }

    public function setMatch($index)
    {
        if ($index > 0) {
            $prevMatch = $this->matches[$index - 1];
            if ($prevMatch->status !== 'completed') {
                return;
            }
        }
        $this->currentMatchIndex = $index;
        $this->series->load('matches');
    }

    public function setWinner($team)
    {
        $match = $this->matches[$this->currentMatchIndex];
        $match->winner_team = $team;
        $match->save();
        
        $this->series->load('matches');
        $this->checkSeriesOver();
    }

    public function checkSeriesOver()
    {
        $winsA = $this->series->matches->where('winner_team', 'team_a')->count();
        $winsB = $this->series->matches->where('winner_team', 'team_b')->count();
        $requiredWins = $this->series->type === 'bo3' ? 2 : 3;

        if ($winsA >= $requiredWins) {
            $this->series->winner_team = 'team_a';
            $this->series->status = 'completed';
            $this->series->save();
        } elseif ($winsB >= $requiredWins) {
            $this->series->winner_team = 'team_b';
            $this->series->status = 'completed';
            $this->series->save();
        }
    }

    public function getIsSeriesOverProperty()
    {
        return $this->series->winner_team !== null;
    }

    public function selectChampion($championId)
    {
        $match = $this->matches[$this->currentMatchIndex];
        
        if ($match->current_turn_index >= count(self::TURNS)) {
            return;
        }

        $allSelected = array_merge($match->blue_bans ?? [], $match->red_bans ?? [], $match->blue_picks ?? [], $match->red_picks ?? []);
        if (in_array($championId, $allSelected)) {
            return;
        }

        if (in_array($championId, $this->fearlessBlockedChampions)) {
            return;
        }

        $currentTurn = self::TURNS[$match->current_turn_index];
        $key = $currentTurn['team'] . '_' . $currentTurn['action'] . 's';

        $arr = $match->$key ?? [];
        $arr[] = $championId;
        $match->$key = $arr;
        
        $match->current_turn_index++;
        
        $champ = collect($this->champions)->firstWhere('id', $championId);
        if ($champ && $champ['is_priority'] && $currentTurn['action'] === 'pick') {
            $prio = $match->priorities_selected ?? [];
            if (!in_array($championId, $prio)) {
                $prio[] = $championId;
                $match->priorities_selected = $prio;
            }
        }

        if ($match->current_turn_index >= count(self::TURNS)) {
            $match->status = 'completed';
        }
        
        $match->save();
    }

    public function undoLastTurn()
    {
        $match = $this->matches[$this->currentMatchIndex];
        
        if ($match->current_turn_index === 0) {
            return;
        }

        $match->current_turn_index--;
        
        $currentTurn = self::TURNS[$match->current_turn_index];
        $key = $currentTurn['team'] . '_' . $currentTurn['action'] . 's';

        $arr = $match->$key ?? [];
        if (!empty($arr)) {
            $removedId = array_pop($arr);
            $match->$key = $arr;

            if ($currentTurn['action'] === 'pick') {
                $prio = $match->priorities_selected ?? [];
                if (($prioKey = array_search($removedId, $prio)) !== false) {
                    unset($prio[$prioKey]);
                    $match->priorities_selected = array_values($prio);
                }
            }
        }

        $match->status = 'drafting';
        $match->winner_team = null;
        $match->save();

        if ($this->series->status === 'completed' || $this->series->winner_team) {
            $this->series->status = 'drafting';
            $this->series->winner_team = null;
            $this->series->save();
        }

        $this->series->load('matches');
    }

    public function getPreviousBansForTeam($teamName)
    {
        $bansByGame = [];
        foreach ($this->matches as $index => $match) {
            if ($index < $this->currentMatchIndex && $match->status === 'completed') {
                $isTeamABlue = $index % 2 === 0;
                $teamABanList = $isTeamABlue ? $match->blue_bans : $match->red_bans;
                $teamBBanList = $isTeamABlue ? $match->red_bans : $match->blue_bans;

                if ($this->series->team_a_name === $teamName) {
                    $bansByGame[] = [
                        'game' => $index + 1,
                        'bans' => $teamABanList ?? []
                    ];
                } else {
                    $bansByGame[] = [
                        'game' => $index + 1,
                        'bans' => $teamBBanList ?? []
                    ];
                }
            }
        }
        return $bansByGame;
    }

    public function getTeamWins($teamName)
    {
        $wins = 0;
        foreach ($this->matches as $match) {
            if ($match->winner_team) {
                $winnerName = $match->winner_team === 'team_a' ? $this->series->team_a_name : $this->series->team_b_name;
                if ($winnerName === $teamName) {
                    $wins++;
                }
            }
        }
        return $wins;
    }
};
?>

<div class="h-screen max-h-screen overflow-hidden flex flex-col p-4 bg-gray-950">
    <div class="mb-3 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center space-x-4">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-1.5 px-3 py-1.5 bg-gray-900 hover:bg-gray-800 border border-gray-800 hover:border-gray-700 text-gray-300 hover:text-white rounded-lg transition text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                <span>Menu Principal</span>
            </a>
            <h1 class="text-2xl font-bold text-white">{{ $series->title }}</h1>
            <span class="px-2 py-1 bg-gray-800 text-gray-300 rounded text-sm">{{ strtoupper($series->type) }}</span>
            <span class="text-indigo-400 font-bold text-lg px-2 bg-gray-900/60 rounded border border-gray-800">
                {{ $this->getTeamWins($series->team_a_name) }} - {{ $this->getTeamWins($series->team_b_name) }}
            </span>
        </div>
        
        <div class="flex space-x-2">
            @foreach($this->matches as $index => $match)
                @if(!$this->isSeriesOver || $match->status === 'completed' || $match->winner_team)
                    @php
                        $isLocked = $index > 0 && $this->matches[$index - 1]->status !== 'completed';
                    @endphp
                    <button wire:click="setMatch({{ $index }})" 
                            @disabled($isLocked || $this->isSeriesOver)
                            class="px-3 py-1 rounded text-sm {{ $currentMatchIndex === $index ? 'bg-indigo-600 text-white' : ($isLocked ? 'bg-gray-800 text-gray-600 cursor-not-allowed opacity-50' : 'bg-gray-800 text-gray-300 hover:bg-gray-700') }}">
                        Jogo {{ $index + 1 }}
                    </button>
                @endif
            @endforeach
        </div>
        
        <div class="flex space-x-4">
            @if(collect($this->matches)->last()->status === 'completed' || $this->isSeriesOver)
                <a href="{{ route('series.summary', ['series' => $series->id]) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold transition">
                    Finalizar Série
                </a>
            @endif
        </div>
    </div>

    @php
        $match = $this->matches[$currentMatchIndex] ?? null;
        $currentTurn = $match && $match->current_turn_index < count(self::TURNS) ? self::TURNS[$match->current_turn_index] : null;
        $isMatchCompleted = $match && $match->status === 'completed';
    @endphp

    @if($this->isSeriesOver)
        <div class="mb-6 p-4 bg-yellow-900/50 border border-yellow-700 rounded-lg text-center shadow-lg">
            <h2 class="text-2xl font-bold text-yellow-500 mb-2">🎉 Série Finalizada! 🎉</h2>
            <p class="text-white text-lg">A equipe <span class="font-bold">{{ $series->winner_team === 'team_a' ? $series->team_a_name : $series->team_b_name }}</span> venceu a série!</p>
        </div>
    @elseif($isMatchCompleted && !$match->winner_team)
        <div class="mb-6 p-6 bg-gray-800 border border-indigo-500 rounded-lg shadow-2xl text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/20 to-red-900/20 z-0"></div>
            <div class="relative z-10">
                <h2 class="text-2xl font-bold text-white mb-4">Fim do Jogo {{ $match->match_number }}</h2>
                <p class="text-gray-300 mb-6">Selecione qual equipe venceu esta partida para continuar:</p>
                <div class="flex justify-center space-x-6">
                    <button wire:click="setWinner('team_a')" class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition transform hover:scale-105">
                        🏆 Vitória: {{ $series->team_a_name }}
                    </button>
                    <button wire:click="setWinner('team_b')" class="px-6 py-3 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg transition transform hover:scale-105">
                        🏆 Vitória: {{ $series->team_b_name }}
                    </button>
                </div>
            </div>
        </div>
    @elseif($isMatchCompleted && $match->winner_team)
        <div class="mb-6 p-4 bg-green-900/30 border border-green-800 rounded-lg text-center flex flex-col items-center justify-center space-y-2">
            <p class="text-green-400 font-bold">Vitória de {{ $match->winner_team === 'team_a' ? $series->team_a_name : $series->team_b_name }} neste jogo.</p>
            
            <button wire:click="undoLastTurn" class="px-3 py-1 bg-red-900/50 hover:bg-red-800/60 border border-red-700 text-red-200 text-xs rounded transition flex items-center space-x-1 font-semibold uppercase tracking-wider">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                <span>Resetar Vencedor e Voltar ao Draft</span>
            </button>
        </div>
    @endif

    @if($match)
    <div class="grid grid-cols-12 gap-4 h-0 flex-grow overflow-hidden">
        <!-- BLUE SIDE -->
        <div class="col-span-3 bg-gray-900 border border-blue-900 rounded-lg p-3 shadow-xl flex flex-col space-y-3 overflow-y-auto custom-scrollbar">
            <h2 class="text-blue-500 font-bold text-2xl mb-1 text-center">{{ $this->blueTeamName }}</h2>
            
            @php
                $wins = $this->getTeamWins($this->blueTeamName);
                $totalDots = $series->type === 'bo3' ? 2 : 3;
            @endphp
            <div class="flex justify-center space-x-2 mb-2">
                @for($i = 0; $i < $totalDots; $i++)
                    <div class="w-3 h-3 rounded-full border-2 {{ $i < $wins ? 'bg-blue-500 border-blue-400 shadow-[0_0_8px_rgba(59,130,246,0.8)]' : 'bg-gray-800 border-gray-700' }}"></div>
                @endfor
            </div>

            <div class="text-gray-400 text-sm mb-2 text-center">BLUE SIDE</div>
            
            <!-- Bans -->
            <div class="flex justify-between mb-2 space-x-1 px-2">
                @for($i = 0; $i < 5; $i++)
                    @php 
                        $bans = $match->blue_bans ?? [];
                        $banId = $bans[$i] ?? null; 
                    @endphp
                    <div class="w-9 h-9 bg-gray-800 border-2 {{ $currentTurn && $currentTurn['team'] === 'blue' && $currentTurn['action'] === 'ban' && count($bans) === $i ? 'border-blue-500 animate-pulse' : 'border-gray-700' }} rounded flex items-center justify-center overflow-hidden grayscale">
                        @if($banId)
                            @php $champ = collect($champions)->firstWhere('id', $banId); @endphp
                            <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                @endfor
            </div>

            <!-- Picks (Blue) — imagem à esquerda, nome à direita -->
            <div class="space-y-2">
                @for($i = 0; $i < 5; $i++)
                    @php 
                        $picks = $match->blue_picks ?? [];
                        $pickId = $picks[$i] ?? null;
                        $isActivePick = $currentTurn && $currentTurn['team'] === 'blue' && $currentTurn['action'] === 'pick' && count($picks) === $i;
                    @endphp
                    <div class="h-[48px] bg-gray-800 border {{ $isActivePick ? 'border-blue-500 animate-pulse' : 'border-gray-700' }} rounded overflow-hidden flex items-center">
                        @if($pickId)
                            @php $champ = collect($champions)->firstWhere('id', $pickId); @endphp
                            {{-- Imagem à esquerda com largura fixa --}}
                            <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="h-full w-12 object-cover flex-shrink-0">
                            {{-- Nome ao lado --}}
                            <span class="ml-2 font-bold text-sm text-white truncate flex-1">{{ $champ['name'] }}</span>
                            {{-- Estrela de prioridade à direita --}}
                            @if(in_array($pickId, $match->priorities_selected ?? []))
                                <span class="mr-2 text-yellow-500 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                </span>
                            @endif
                        @endif
                    </div>
                @endfor
            </div>

            <!-- Bans de Jogos Anteriores -->
            @php
                $prevBans = $this->getPreviousBansForTeam($this->blueTeamName);
            @endphp
            @if(!empty($prevBans))
                <div class="mt-3 pt-2 border-t border-gray-800 flex-shrink-0">
                    <div class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider text-center">Bans Anteriores</div>
                    <div class="space-y-2">
                        @foreach($prevBans as $pb)
                            <div class="bg-gray-950/40 p-2 rounded border border-gray-800/80">
                                <div class="text-[10px] text-gray-500 font-bold mb-1">JOGO {{ $pb['game'] }}</div>
                                <div class="flex space-x-1 justify-center">
                                    @foreach($pb['bans'] as $banId)
                                        @php $champ = collect($champions)->firstWhere('id', $banId); @endphp
                                        @if($champ)
                                            <div class="w-7 h-7 rounded overflow-hidden border border-gray-700/50 grayscale" title="{{ $champ['name'] }}">
                                                <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="w-full h-full object-cover">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- CENTER GALLERY -->
        <div class="col-span-6 bg-gray-900 border border-gray-800 rounded-lg p-4 shadow-xl flex flex-col overflow-hidden">
            @if($match->status === 'completed')
                <div class="text-center font-bold text-lg mb-4 text-green-500 uppercase tracking-widest">Draft Finalizado</div>
                <div class="bg-gray-800 p-8 rounded-lg text-center h-full flex flex-col justify-center items-center space-y-4">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Fim da Partida</h3>
                        <p class="text-gray-400">Os campeões escolhidos acima foram bloqueados para os próximos jogos desta série.</p>
                    </div>
                    
                    @if(!$match->winner_team)
                        <button wire:click="undoLastTurn" class="px-4 py-2 bg-red-900/50 hover:bg-red-800/60 border border-red-700 text-red-200 text-sm rounded transition flex items-center space-x-2 font-semibold uppercase tracking-wider mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            <span>Desfazer Última Escolha</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="mb-4 flex flex-col space-y-3 flex-shrink-0">
                    <div class="text-center font-bold text-lg mb-1">
                        @if($currentTurn)
                            Turno de: <span class="{{ $currentTurn['team'] === 'blue' ? 'text-blue-500' : 'text-red-500' }} uppercase">{{ $currentTurn['team'] }}</span>
                            Ação: <span class="{{ $currentTurn['action'] === 'ban' ? 'text-red-400' : 'text-green-400' }} uppercase">{{ $currentTurn['action'] }}</span>
                        @endif
                    </div>
                    
                    @if($match->current_turn_index > 0)
                        <div class="flex justify-center">
                            <button wire:click="undoLastTurn" class="px-3 py-1 bg-red-900/50 hover:bg-red-800/60 border border-red-700 text-red-200 text-xs rounded transition flex items-center space-x-1 font-semibold uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                <span>Desfazer Última Escolha</span>
                            </button>
                        </div>
                    @endif
                    
                    <!-- Filtros -->
                    <div class="space-y-2">
                        <input type="text" wire:model.live="search" placeholder="Pesquisar campeão..." class="w-full bg-gray-800 border border-gray-700 text-white px-3 py-2 rounded text-sm focus:outline-none focus:border-indigo-500 placeholder-gray-500">
                        
                        <div class="flex space-x-1">
                            <button wire:click="selectRole('')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === '' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Todos</button>
                            <button wire:click="selectRole('Top')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === 'Top' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Top</button>
                            <button wire:click="selectRole('Jungle')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === 'Jungle' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Jungle</button>
                            <button wire:click="selectRole('Mid')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === 'Mid' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Mid</button>
                            <button wire:click="selectRole('ADC')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === 'ADC' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">ADC</button>
                            <button wire:click="selectRole('Support')" class="flex-1 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition-colors {{ $selectedRole === 'Support' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">Sup</button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-6 gap-2 h-0 flex-grow overflow-y-auto pr-2 custom-scrollbar content-start">
                    @foreach($this->filteredChampions as $champ)
                        @php
                            $isSelected = in_array($champ['id'], array_merge($match->blue_bans ?? [], $match->red_bans ?? [], $match->blue_picks ?? [], $match->red_picks ?? []));
                            $isFearlessBlocked = in_array($champ['id'], $this->fearlessBlockedChampions);
                            $isDisabled = $isSelected || $isFearlessBlocked || !$currentTurn;
                        @endphp
                        {{-- Wrapper com padding-bottom: 100% garante card quadrado independente da altura do grid --}}
                        <div class="relative w-full" style="padding-bottom: 100%;">
                            <div 
                                @if(!$isDisabled) wire:click="selectChampion({{ $champ['id'] }})" @endif
                                class="absolute inset-0 rounded overflow-hidden border-2 transition-transform hover:scale-105 {{ $isDisabled ? 'opacity-30 cursor-not-allowed border-gray-800 pointer-events-none' : 'cursor-pointer ' . ($champ['is_priority'] ? 'border-yellow-500' : 'border-gray-700') }}"
                            >
                                <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="absolute inset-0 w-full h-full object-cover {{ $isFearlessBlocked ? 'grayscale' : '' }}">
                                @if($champ['is_priority'])
                                    <div class="absolute top-1 right-1 text-yellow-500 z-10">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    </div>
                                @endif
                                <div class="absolute bottom-0 inset-x-0 bg-black/60 text-xs text-center py-1 truncate px-1 z-10">
                                    {{ $champ['name'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- RED SIDE -->
        <div class="col-span-3 bg-gray-900 border border-red-900 rounded-lg p-3 shadow-xl flex flex-col space-y-3 overflow-y-auto custom-scrollbar">
            <h2 class="text-red-500 font-bold text-2xl mb-1 text-center">{{ $this->redTeamName }}</h2>
            
            @php
                $wins = $this->getTeamWins($this->redTeamName);
                $totalDots = $series->type === 'bo3' ? 2 : 3;
            @endphp
            <div class="flex justify-center space-x-2 mb-2">
                @for($i = 0; $i < $totalDots; $i++)
                    <div class="w-3 h-3 rounded-full border-2 {{ $i < $wins ? 'bg-red-500 border-red-400 shadow-[0_0_8px_rgba(239,68,68,0.8)]' : 'bg-gray-800 border-gray-700' }}"></div>
                @endfor
            </div>

            <div class="text-gray-400 text-sm mb-2 text-center">RED SIDE</div>
            
            <!-- Bans -->
            <div class="flex justify-between mb-2 space-x-1 px-2">
                @for($i = 0; $i < 5; $i++)
                    @php 
                        $bans = $match->red_bans ?? [];
                        $banId = $bans[$i] ?? null; 
                    @endphp
                    <div class="w-9 h-9 bg-gray-800 border-2 {{ $currentTurn && $currentTurn['team'] === 'red' && $currentTurn['action'] === 'ban' && count($bans) === $i ? 'border-red-500 animate-pulse' : 'border-gray-700' }} rounded flex items-center justify-center overflow-hidden grayscale">
                        @if($banId)
                            @php $champ = collect($champions)->firstWhere('id', $banId); @endphp
                            <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                @endfor
            </div>

            <!-- Picks (Red) — nome à esquerda, imagem à direita -->
            <div class="space-y-2">
                @for($i = 0; $i < 5; $i++)
                    @php 
                        $picks = $match->red_picks ?? [];
                        $pickId = $picks[$i] ?? null;
                        $isActivePick = $currentTurn && $currentTurn['team'] === 'red' && $currentTurn['action'] === 'pick' && count($picks) === $i;
                    @endphp
                    <div class="h-[48px] bg-gray-800 border {{ $isActivePick ? 'border-red-500 animate-pulse' : 'border-gray-700' }} rounded overflow-hidden flex items-center">
                        @if($pickId)
                            @php $champ = collect($champions)->firstWhere('id', $pickId); @endphp
                            {{-- Estrela de prioridade à esquerda --}}
                            @if(in_array($pickId, $match->priorities_selected ?? []))
                                <span class="ml-2 text-yellow-500 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                </span>
                            @endif
                            {{-- Nome à esquerda --}}
                            <span class="mr-2 font-bold text-sm text-white truncate flex-1 text-right {{ in_array($pickId, $match->priorities_selected ?? []) ? 'ml-1' : 'ml-2' }}">{{ $champ['name'] }}</span>
                            {{-- Imagem à direita com largura fixa --}}
                            <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="h-full w-12 object-cover flex-shrink-0">
                        @endif
                    </div>
                @endfor
            </div>

            <!-- Bans de Jogos Anteriores -->
            @php
                $prevBans = $this->getPreviousBansForTeam($this->redTeamName);
            @endphp
            @if(!empty($prevBans))
                <div class="mt-3 pt-2 border-t border-gray-800 flex-shrink-0">
                    <div class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider text-center">Bans Anteriores</div>
                    <div class="space-y-2">
                        @foreach($prevBans as $pb)
                            <div class="bg-gray-950/40 p-2 rounded border border-gray-800/80">
                                <div class="text-[10px] text-gray-500 font-bold mb-1">JOGO {{ $pb['game'] }}</div>
                                <div class="flex space-x-1 justify-center">
                                    @foreach($pb['bans'] as $banId)
                                        @php $champ = collect($champions)->firstWhere('id', $banId); @endphp
                                        @if($champ)
                                            <div class="w-7 h-7 rounded overflow-hidden border border-gray-700/50 grayscale" title="{{ $champ['name'] }}">
                                                <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="w-full h-full object-cover">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>