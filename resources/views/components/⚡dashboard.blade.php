<?php

use Livewire\Component;
use App\Models\Series;

new class extends Component
{
    public function with()
    {
        return [
            'recentSeries' => Series::orderBy('created_at', 'desc')->get()
        ];
    }
};
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <header class="mb-10 text-center">
        <h1 class="text-5xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500 mb-2">Wild Rift Tournament</h1>
        <p class="text-gray-400 text-lg">Gerencie séries competitivas e campeões</p>
    </header>

    <div class="flex justify-center space-x-4 mb-12">
        <a href="{{ route('series.create') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg transition-transform transform hover:-translate-y-1">Nova Série</a>
        <a href="{{ route('champions.index') }}" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold shadow-lg transition-transform transform hover:-translate-y-1">Gerenciar Campeões</a>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden shadow-2xl p-6">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-gray-800 pb-2">Séries Recentes</h2>
        
        @if($recentSeries->isEmpty())
            <div class="text-center py-10 text-gray-500">
                <p>Nenhuma série cadastrada ainda.</p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach($recentSeries as $series)
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 flex items-center justify-between hover:bg-gray-750 transition-colors">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1">{{ $series->title }}</h3>
                            <div class="flex items-center space-x-3 text-sm">
                                <span class="text-blue-400 font-semibold">{{ $series->team_a_name }}</span>
                                <span class="text-gray-500">VS</span>
                                <span class="text-red-400 font-semibold">{{ $series->team_b_name }}</span>
                                <span class="px-2 py-1 bg-gray-700 text-gray-300 rounded text-xs ml-2">{{ strtoupper($series->type) }}</span>
                                @if($series->winner_team)
                                    <span class="px-2 py-1 bg-yellow-900/50 text-yellow-500 rounded text-xs">
                                        Vencedor: {{ $series->winner_team === 'team_a' ? $series->team_a_name : $series->team_b_name }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 {{ $series->status === 'completed' ? 'bg-green-900/50 text-green-400' : 'bg-blue-900/50 text-blue-400' }} rounded text-xs">
                                        {{ $series->status === 'completed' ? 'Finalizada' : 'Em Andamento' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            @if($series->status !== 'completed' && !$series->winner_team)
                                <a href="{{ route('series.draft', ['series' => $series->id]) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded transition">Continuar Draft</a>
                            @endif
                            <a href="{{ route('series.summary', ['series' => $series->id]) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-semibold rounded transition">Ver Resumo</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>