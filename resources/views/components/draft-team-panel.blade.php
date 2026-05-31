@props([
    'side', // 'blue' or 'red'
    'teamName',
    'wins',
    'totalDots',
    'match',
    'currentTurn',
    'champions',
    'prevPicks'
])

@php
    $isBlue = $side === 'blue';
    $themeColor = $isBlue ? 'blue' : 'red';
    $sideLabel = $isBlue ? 'BLUE SIDE' : 'RED SIDE';
    $accentBorder = $isBlue ? 'border-blue-900' : 'border-red-900';
    $dotsColorClass = $isBlue 
        ? 'bg-blue-500 border-blue-400 shadow-[0_0_8px_rgba(59,130,246,0.8)]' 
        : 'bg-red-500 border-red-400 shadow-[0_0_8px_rgba(239,68,68,0.8)]';
    $titleColorClass = $isBlue ? 'text-blue-500' : 'text-red-500';
@endphp

<div class="col-span-3 bg-gray-900 border {{ $accentBorder }} rounded-lg p-3 shadow-xl flex flex-col space-y-3 overflow-y-auto custom-scrollbar">
    <h2 class="{{ $titleColorClass }} font-bold text-2xl mb-1 text-center">{{ $teamName }}</h2>
    
    <div class="flex justify-center space-x-2 mb-2">
        @for($i = 0; $i < $totalDots; $i++)
            <div class="w-3 h-3 rounded-full border-2 {{ $i < $wins ? $dotsColorClass : 'bg-gray-800 border-gray-700' }}"></div>
        @endfor
    </div>

    <div class="text-gray-400 text-sm mb-2 text-center">{{ $sideLabel }}</div>
    
    <!-- Bans -->
    <div class="flex justify-between mb-2 space-x-1 px-2">
        @for($i = 0; $i < 5; $i++)
            @php 
                $bans = $isBlue ? ($match->blue_bans ?? []) : ($match->red_bans ?? []);
                $banId = $bans[$i] ?? null;
                $isActiveBan = $currentTurn && $currentTurn['team'] === $side && $currentTurn['action'] === 'ban' && count($bans) === $i;
                $banBorderColor = $isActiveBan ? ($isBlue ? 'border-blue-500 animate-pulse' : 'border-red-500 animate-pulse') : 'border-gray-700';
            @endphp
            <div class="w-9 h-9 bg-gray-800 border-2 {{ $banBorderColor }} rounded flex items-center justify-center overflow-hidden grayscale">
                @if($banId)
                    @php $champ = $champions[$banId] ?? null; @endphp
                    @if($champ)
                        <img src="{{ $champ['image_url'] }}" alt="{{ $champ['name'] }}" class="w-full h-full object-cover">
                    @endif
                @endif
            </div>
        @endfor
    </div>

    <!-- Picks -->
    <div class="space-y-2">
        @for($i = 0; $i < 5; $i++)
            @php 
                $picks = $isBlue ? ($match->blue_picks ?? []) : ($match->red_picks ?? []);
                $pickId = $picks[$i] ?? null;
                $isActivePick = $currentTurn && $currentTurn['team'] === $side && $currentTurn['action'] === 'pick' && count($picks) === $i;
                $pickBorderColor = $isActivePick ? ($isBlue ? 'border-blue-500 animate-pulse' : 'border-red-500 animate-pulse') : 'border-gray-700';
            @endphp
            <div class="h-[48px] bg-gray-800 border {{ $pickBorderColor }} rounded overflow-hidden flex items-center">
                @if($pickId)
                    @php $champ = $champions[$pickId] ?? null; @endphp
                    @if($champ)
                        @if($isBlue)
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
                        @else
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
                    @endif
                @endif
            </div>
        @endfor
    </div>

    <!-- Picks de Jogos Anteriores -->
    @if(!empty($prevPicks))
        <div class="mt-3 pt-2 border-t border-gray-800 flex-shrink-0">
            <div class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider text-center">Picks Anteriores</div>
            <div class="space-y-2">
                @foreach($prevPicks as $pp)
                    <div class="bg-gray-950/40 p-2 rounded border border-gray-800/80">
                        <div class="text-[10px] text-gray-500 font-bold mb-1 text-center">JOGO {{ $pp['game'] }}</div>
                        <div class="flex space-x-1 justify-center">
                            @foreach($pp['picks'] as $pickId)
                                @php $champ = $champions[$pickId] ?? null; @endphp
                                @if($champ)
                                    <div class="w-7 h-7 rounded overflow-hidden border border-gray-700/50" title="{{ $champ['name'] }}">
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
