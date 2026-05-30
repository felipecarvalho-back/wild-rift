# ⚡ Wild Rift Tournament Draft Simulator

Um simulador de Draft de torneio de Wild Rift premium e interativo, construído com o estado da arte do ecossistema Laravel. O simulador permite gerenciar séries de campeonatos no formato **Fearless Draft** (onde campeões já jogados são banidos nas partidas seguintes) com suporte a regras competitivas oficiais, controle de rotas principais e adicionais, indicadores de vitórias e gerenciador completo de campeões.

---

## 🚀 Tecnologias Utilizadas

- **PHP 8.5** & **Laravel 13**
- **Livewire 4** (Single File Components - SFC)
- **Tailwind CSS v4** (Interface escura premium e moderna)
- **Alpine.js** (Interações fluidas no cliente)
- **SQLite** (Banco de dados leve e eficiente)
- **Pest PHP** (Framework moderno de testes unitários e de feature)

---

## ✨ Funcionalidades Principais

- **Simulador de Draft Oficial (5 Bans / 5 Picks)**:
  - Segue estritamente as regras competitivas oficiais.
  - Ordem visual do Pick Slot otimizada (Blue side: Imagem à esquerda e texto à direita; Red side: Texto à esquerda e imagem à direita).
  - Indicador dinâmico de prioridade (bordas douradas e estrelas para campeões definidos como "Priority Picks").
  - Histórico visual de bans de jogos anteriores exibido abaixo de cada equipe durante o draft ativo.

- **Sistema de Fearless Draft**:
  - Bloqueio automático de campeões escolhidos em partidas anteriores para as partidas subsequentes da mesma série.

- **Botão de Desfazer (Undo Last Pick/Ban)**:
  - Permite reverter instantaneamente a última alteração (seja pick ou ban) ou redefinir o vencedor de uma partida concluída.

- **Gerenciador de Campeões Completo**:
  - Cadastro, edição e exclusão de campeões.
  - Mapeamento de **Rota Principal** e **Rotas Adicionais** (opcional) com busca aprimorada por múltiplas rotas (ex: suporte, selva).
  - Marcador de **Prioridade** que posiciona os campeões prioritários no topo do grid de seleção com destaques dourados.
  - Interface otimizada que limpa a busca textual automaticamente ao alterar o filtro de rotas.

- **Navegabilidade Aperfeiçoada**:
  - Botões rápidos e elegantes de volta ao menu principal adicionados em todas as telas chave da aplicação (Draft, Resumo, etc.).

---

## 🛠️ Arquitetura do Banco de Dados

### 1. `Champion`
Representa os campeões disponíveis no simulador com imagens providas da API oficial da Riot (DDragon CDN).
- `name`: Nome do campeão.
- `role`: Rota principal (`Top`, `Jungle`, `Mid`, `ADC`, `Support`).
- `secondary_role`: Lista de rotas adicionais salvas como strings separadas por vírgula.
- `image_url`: Link da imagem oficial.
- `is_priority`: Indicador lógico para destaque do campeão.

### 2. `Series`
Representa a série melhor de 3 ou melhor de 5 de um campeonato.
- `title`: Nome/Título da série.
- `type`: Tipo de série (`bo3` ou `bo5`).
- `status`: Status (`drafting`, `completed`).
- `team_a_name`: Nome da equipe A.
- `team_b_name`: Nome da equipe B.
- `winner_team`: Vencedor final da série (`team_a` ou `team_b`).

### 3. `GameMatch`
Representa uma partida específica dentro de uma série.
- `series_id`: ID de referência da série.
- `match_number`: Número da partida (Jogo 1, 2, 3, etc.).
- `blue_bans` / `red_bans`: Arrays contendo os IDs dos campeões banidos por cada lado.
- `blue_picks` / `red_picks`: Arrays contendo os IDs dos campeões escolhidos por cada lado.
- `current_turn_index`: Índice atual da fase de draft (turno do ban/pick).
- `status`: Estado atual da partida (`drafting`, `completed`).
- `winner_team`: Vencedor da partida (`team_a` ou `team_b`).
- `priorities_selected`: Registro de quais picks prioritários foram selecionados.

---

## 💻 Instalação e Execução

### Pré-requisitos
Certifique-se de ter instalado em sua máquina:
- PHP >= 8.5
- Composer
- Node.js & NPM

### Passo a Passo

1. **Clonar o Repositório**:
   ```bash
   git clone <url-do-repositorio>
   cd wild-rift
   ```

2. **Instalar Dependências**:
   ```bash
   composer install
   npm install
   ```

3. **Configurar o Ambiente**:
   Copie o arquivo `.env.example` para `.env` e configure sua chave de aplicativo:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Executar Migrações e Populadores**:
   Execute o fresh com seeding para instalar o banco de dados SQLite pré-configurado com **todos os 139 campeões** de Wild Rift contendo URLs oficiais DDragon 16.11.1 atualizadas (incluindo Victor/Viktor VGU, Ambessa, Aurora, Mel, Norra e Rell):
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Iniciar Servidores de Desenvolvimento**:
   Para rodar localmente a aplicação com compilação dinâmica e servidor web ativo:
   ```bash
   composer run dev
   ```
   *Nota: O comando acima rodará simultaneamente o servidor do Laravel e a build em tempo real do Vite para compilar o Tailwind CSS v4.*

---

## 🧪 Testes Automatizados

O projeto conta com testes modernos integrados utilizando Pest PHP para validar as principais rotas da aplicação em um banco de dados temporário de testes SQLite.

Para rodar a suíte de testes:
```bash
php artisan test --compact
```
