<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Champion;

new class extends Component
{
    public $search = '';
    public $roleFilter = '';
    
    public $showModal = false;
    public $editingId = null;
    
    public $name = '';
    public $role = 'Top';
    public $secondary_role = '';
    public $secondary_roles = [];
    public $image_url = '';

    protected $rules = [
        'name' => 'required|string|max:100',
        'role' => 'required|in:Top,Jungle,Mid,ADC,Support',
        'secondary_roles' => 'array',
        'secondary_roles.*' => 'in:Top,Jungle,Mid,ADC,Support',
        'image_url' => 'nullable|url',
    ];

    public function with()
    {
        $query = Champion::query();
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        
        if ($this->roleFilter) {
            $query->where(function ($q) {
                $q->where('role', $this->roleFilter)
                  ->orWhere('secondary_role', 'like', '%' . $this->roleFilter . '%');
            });
        }
        
        return [
            'champions' => $query->orderBy('name')->get()
        ];
    }

    public function togglePriority($championId)
    {
        $champion = Champion::find($championId);
        if ($champion) {
            $champion->is_priority = !$champion->is_priority;
            $champion->save();
        }
    }
    
    public function setRoleFilter($role)
    {
        $this->roleFilter = $this->roleFilter === $role ? '' : $role;
        $this->search = '';
    }

    public function create()
    {
        $this->resetValidation();
        $this->reset(['name', 'role', 'secondary_role', 'secondary_roles', 'image_url', 'editingId']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $champion = Champion::findOrFail($id);
        $this->editingId = $champion->id;
        $this->name = $champion->name;
        $this->role = $champion->role;
        $this->secondary_role = $champion->secondary_role;
        $this->secondary_roles = $champion->secondary_role ? explode(',', $champion->secondary_role) : [];
        $this->image_url = $champion->image_url;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'role' => $this->role,
            'secondary_role' => !empty($this->secondary_roles) ? implode(',', $this->secondary_roles) : null,
            'image_url' => $this->image_url,
        ];

        if ($this->editingId) {
            $champion = Champion::find($this->editingId);
            $champion->update($data);
        } else {
            $data['is_priority'] = false;
            Champion::create($data);
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        Champion::destroy($id);
    }
};
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white flex items-center transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Voltar ao Dashboard
        </a>
    </div>

    <header class="mb-8 flex justify-between items-center border-b border-gray-800 pb-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-white mb-2">Gerenciar Campeões</h1>
            <p class="text-gray-400">Cadastre e edite as informações dos campeões do jogo</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow transition">
            + Novo Campeão
        </button>
    </header>

    <!-- Filtros -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 shadow-xl mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex-1">
                <input wire:model.live="search" type="text" placeholder="Buscar campeão pelo nome..." class="w-full bg-gray-800 border border-gray-700 text-white px-4 py-2 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="flex space-x-2">
                @php $roles = ['Top', 'Jungle', 'Mid', 'ADC', 'Support']; @endphp
                @foreach($roles as $rFilter)
                    <button wire:click="setRoleFilter('{{ $rFilter }}')" class="px-3 py-1 rounded text-sm transition {{ $roleFilter === $rFilter ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                        {{ $rFilter === 'Support' ? 'Sup' : $rFilter }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
        @foreach($champions as $champ)
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex flex-col items-center shadow-lg transition-transform hover:-translate-y-1 group relative overflow-hidden">
                @if($champ->is_priority)
                    <div class="absolute top-0 right-0 m-2 text-yellow-500" title="Alta Prioridade">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                @endif
                
                <div class="w-20 h-20 rounded-full border-2 {{ $champ->is_priority ? 'border-yellow-500' : 'border-gray-700' }} overflow-hidden mb-3">
                    <img src="{{ $champ->image_url ?: 'https://via.placeholder.com/100' }}" alt="{{ $champ->name }}" class="w-full h-full object-cover">
                </div>
                
                <h3 class="font-bold text-white text-center">{{ $champ->name }}</h3>
                <span class="text-xs text-indigo-400 mb-4">
                    {{ $champ->role === 'Support' ? 'Sup' : $champ->role }}
                    @if($champ->secondary_role)
                        / {{ str_replace('Support', 'Sup', $champ->secondary_role) }}
                    @endif
                </span>
                
                <!-- Ações -->
                <div class="w-full flex space-x-1 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity mt-auto">
                    <button wire:click="edit({{ $champ->id }})" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white text-xs py-1 rounded transition">Editar</button>
                    <button wire:click="togglePriority({{ $champ->id }})" class="flex-1 {{ $champ->is_priority ? 'bg-yellow-700 hover:bg-yellow-600' : 'bg-blue-700 hover:bg-blue-600' }} text-white text-xs py-1 rounded transition">
                        {{ $champ->is_priority ? '- Prior.' : '+ Prior.' }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($champions->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>Nenhum campeão encontrado para este filtro.</p>
        </div>
    @endif

    <!-- Modal CRUD -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
            <div class="bg-gray-900 border border-gray-700 rounded-xl max-w-md w-full shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white">{{ $editingId ? 'Editar Campeão' : 'Novo Campeão' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-white">&times;</button>
                </div>
                
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Nome do Campeão</label>
                        <input type="text" wire:model="name" class="w-full bg-gray-800 border border-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:border-blue-500">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Rota Principal</label>
                            <select wire:model="role" class="w-full bg-gray-800 border border-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:border-blue-500">
                                <option value="Top">Top Lane</option>
                                <option value="Jungle">Jungle</option>
                                <option value="Mid">Mid Lane</option>
                                <option value="ADC">ADC / Dragon Lane</option>
                                <option value="Support">Sup</option>
                            </select>
                            @error('role') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-400 mb-1">Rotas Adicionais (Opcional)</label>
                            <div class="flex flex-wrap gap-2 pt-1">
                                @foreach(['Top', 'Jungle', 'Mid', 'ADC', 'Support'] as $r)
                                    @if($r !== $this->role)
                                        <label class="inline-flex items-center bg-gray-800 border border-gray-700 px-3 py-1.5 rounded-lg text-xs text-white cursor-pointer hover:bg-gray-700 transition">
                                            <input type="checkbox" wire:model="secondary_roles" value="{{ $r }}" class="form-checkbox text-indigo-600 rounded mr-2 focus:ring-0 focus:ring-offset-0 bg-gray-900 border-gray-700">
                                            <span>{{ $r === 'Support' ? 'Sup' : $r }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            @error('secondary_roles') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">URL da Imagem</label>
                        <input type="text" wire:model="image_url" placeholder="https://..." class="w-full bg-gray-800 border border-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:border-blue-500">
                        @error('image_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-gray-800 flex justify-end space-x-3">
                        @if($editingId)
                            <button type="button" wire:click="delete({{ $editingId }})" class="mr-auto px-4 py-2 bg-red-600/20 text-red-500 hover:bg-red-600 hover:text-white rounded transition">Excluir</button>
                        @endif
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded transition">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>