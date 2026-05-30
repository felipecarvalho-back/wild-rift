# Especificação de Projeto: Draft Simulator & Manager - Wild Rift

Este documento serve como a especificação completa e estruturada para a geração da aplicação web de **Montador e Simulador de Draft para Wild Rift**.

A aplicação permitirá a simulação completa de escolhas (picks) e banimentos (bans), cadastro de campeões, gerenciamento de séries (MD3/MD5) e marcação dinâmica de prioridades.

---

## 1. Visão Geral da Aplicação
Uma ferramenta interativa voltada para times, técnicos e analistas de **Wild Rift** planejarem e executarem simulações de draft competitivos. A aplicação deve suportar o controle rígido de regras de alternância, gerenciamento de séries completas de partidas e um painel analítico/banco de dados para cadastro de campeões e prioridades.

---

## 2. Arquitetura e Tecnologias Recomendadas

Para garantir a persistência dos dados dos campeões, reatividade na escolha dos turnos e facilidade de manutenção, o projeto será construído utilizando a **TALL Stack** (com foco em ecossistema PHP):

* **Backend / Framework:** PHP 8.5 com **Laravel 13**
* **Camada de Reatividade:** **Livewire v4** (para gerenciar o estado do draft em tempo real sem carregar a página)
* **Interações de UI Rápidas:** **Alpine.js** (para filtros instantâneos na galeria e controle de modais)
* **Estilização:** **Tailwind CSS** (para construção de uma interface escura e responsiva padrão e-sports)
* **Banco de Dados:** **MySQL** (para persistência de campeões, séries e configurações de prioridade)

---

## 3. Requisitos de Fluxo e Regras do Draft

O fluxo do draft deve simular fielmente a experiência do cenário competitivo do Wild Rift, baseado nos seguintes parâmetros:

### Lados e Ordem de Seleção
* **Blue Side (Lado Azul):** Sempre inicia escolhendo (Picks). Fica posicionado à esquerda.
* **Red Side (Lado Vermelho):** Sempre possui a última escolha do draft (Counter pick). Fica posicionado à direita.

### Sistema de Banimentos (3 Bans por Lado - Alternados)
O sistema seguirá o modelo sequencial e alternado clássico:
1.  **Blue Side** bane 1º Campeão
2.  **Red Side** bane 1º Campeão
3.  **Blue Side** bane 2º Campeão
4.  **Red Side** bane 2º Campeão
5.  **Blue Side** bane 3º Campeão
6.  **Red Side** bane 3º Campeão

### Sistema de Escolhas (Picks - 5 por Lado)
Após a fase de banimentos, inicia-se a seleção de campeões de forma alternada (Formato: 1-2-2-2-2-1):
1.  **Blue Side** escolhe 1 campeão (B1)
2.  **Red Side** escolhe 2 campeões (R1, R2)
3.  **Blue Side** escolhe 2 campeões (B2, B3)
4.  **Red Side** escolhe 2 campeões (R3, R4)
5.  **Blue Side** escolhe 2 campeões (B4, B5)
6.  **Red Side** escolhe 1 campeão (R5)

*Nota: Um campeão banido ou já escolhido por um lado fica indisponível para o resto daquela partida.*

---

## 4. Estrutura do Sistema de Séries (MD3 / MD5)

O sistema deve gerenciar o estado global de uma série de partidas (Melhor de 3 ou Melhor de 5):

* **Configuração Inicial:** O usuário seleciona o formato da série (**MD3 / Bo3** ou **MD5 / Bo5**).
* **Controle de Partidas:** A série deve conter abas ou um seletor numérico gerenciado pelo Livewire para navegar entre os jogos (ex: `Jogo 1`, `Jogo 2`, `Jogo 3`).
* **Independência de Estado:** Cada jogo na série possui seu próprio tabuleiro de draft isolado no banco de dados ou na sessão. O usuário pode preencher o draft do Jogo 1, avançar, e configurar o Jogo 2.
* **Independência do Placar:** O preenchimento ou alternância dos jogos deve ser livre, sem travar o avanço caso um time ganhe duas seguidas na MD3, permitindo rodar todos os jogos configurados de forma analítica.

---

## 5. Gerenciamento de Campeões e Tela de Cadastro

### Atributos do Campeão (Model `Champion`)
Cada campeão cadastrado no banco de dados deve possuir:
* `id` (Primary Key)
* `name` (string)
* `role` (enum ou string: Rota Solo, Selva, Rota do Meio, Rota Dupla, Suporte)
* `image_path` / `image_url` (string para ícone do campeão)
* `is_priority` (boolean - indica se é uma escolha de alta prioridade no patch atual)

### Tela/Filtro de Prioridade
* **Marcação de Prioridades:** Uma view com uma listagem onde o usuário pode alternar o status `is_priority` via um componente Livewire (ex: um switch/checkbox assíncrono).
* **Exibição Dinâmica no Draft:** Durante a fase de seleção de qualquer jogo da série, os campeões com `is_priority == true` devem receber um **destaque visual claro** (ex: borda dourada, um ícone de estrela ou uma seção separada "Prioridades" no topo da lista de seleção).

---

## 6. Interface do Usuário (UI) Sugerida

Para a geração do código pelo Anti-Gravity, prever a seguinte divisão de componentes Blade/Livewire:

### A. Barra Superior (Header & Configurações de Série)
* Seletor de Tipo de Série (MD3 / MD5)
* Indicadores de status/abas para os jogos (`Jogo 1`, `Jogo 2`, `Jogo 3`...)
* Botão para abrir o Modal Alpine.js de **Cadastro / Prioridades**.

### B. Tela Principal do Draft (Layout de Tabuleiro)
* **Coluna Esquerda (Blue Side):**
    * 3 slots superiores pequenos para os Bans (exibidos com filtro cinza/vermelho).
    * 5 slots grandes verticais para os Picks (mostrando a arte do campeão).
* **Painel Central (Galeria de Campeões - Componente Filtrável):**
    * Barra de pesquisa por nome e filtros por rota (controlados instantaneamente por Alpine/Livewire).
    * **Seção de Campeões de Alta Prioridade** fixada no topo da listagem.
    * Grid com todos os campeões cadastrados disponíveis para clique (respeitando o turno ativo de Ban/Pick).
* **Coluna Direita (Red Side):**
    * 3 slots superiores pequenos para os Bans.
    * 5 slots grandes verticais para os Picks.

---

## 7. Lógica de Interação e Estados (Mapeamento PHP / Livewire)

* **Gerenciador de Turno:** O componente Livewire deve manter uma propriedade `$current_turn` (ex: `BLUE_BAN_1`, `RED_BAN_1`, ..., `BLUE_PICK_1`). A cada clique em um campeão válido, o método `selectChampion($championId)` valida a regra, insere o ID no slot correto e avança o turno.
* **Coleção de Jogos:** O estado de cada jogo (`game_1_picks`, `game_1_bans`, etc.) deve ser armazenado temporariamente em um array público do Livewire ou persistido em uma tabela `series_matches` para evitar perda de dados ao alternar abas.
* **Validações em PHP:** Garantir no backend que o mesmo campeão não seja selecionado mais de uma vez na mesma partida.
