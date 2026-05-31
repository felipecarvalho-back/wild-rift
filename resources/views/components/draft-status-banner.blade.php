@props([
    'isSeriesOver',
    'series',
    'match',
    'currentTurn',
    'isMatchCompleted'
])

@if($isSeriesOver)
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
