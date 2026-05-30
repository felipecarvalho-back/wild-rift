# Especificação de Projeto: Draft Simulator & Manager - Wild Rift (Fase de 5 Bans & Fearless Draft)

Este documento serve como a especificação técnica e funcional completa para a ferramenta **Anti-Gravity** gerar a aplicação web de **Montador e Simulador de Draft para Wild Rift**. 

A aplicação gerencia séries de jogos (MD3/MD5), aplicando regras estritas de rotação de turnos com 5 banimentos por equipe, bloqueio de campeões já utilizados na série e destaque para escolhas prioritárias definidas previamente.

---

## 1. Arquitetura e Tecnologias Obligatorias (TALL Stack)

O projeto deve ser gerado estruturado no ecossistema PHP/Laravel para garantir persistência robusta e reatividade fluida:
* **Backend:** PHP 8.2+ com **Laravel 10/11**
* **Reatividade do Draft:** **Livewire v3** (atualizações em tempo real sem reload de página)
* **Interações de UI e Modais:** **Alpine.js**
* **Estilização:** **Tailwind CSS** (Tema Dark / Estilo e-Sports Competitivo)
* **Banco de Dados:** **MySQL** ou **PostgreSQL**

---

## 2. Configuração do Jogo e Seleção de Prioridades (Pré-Draft)

Antes de iniciar a simulação visual do draft na tela do tabuleiro, o usuário passa por um fluxo de configuração estruturado:

1.  **Cadastro da Série:** O usuário cria uma nova série definindo o formato: **MD3 (Bo3)** ou **MD5 (Bo5)**.
2.  **Definição Pré-Draft de Prioridades:** * Antes do início das escolhas de cada partida (ou globalmente para a série), o usuário acessa uma listagem/grid de campeões.
    * O usuário seleciona quais campeões são considerados **"Prioridade / Power Picks"**.
    * *Comportamento no Draft:* Os campeões selecionados nesta etapa recebem um destaque visual fixo (ex: borda dourada, ícone de estrela) e ficam agrupados em uma seção de destaque no topo do painel de seleção durante todo o draft correspondente.

---

## 3. Fluxo e Regras do Draft (5 Bans - Modelo Competitivo Oficial)

O draft é sem limite de tempo para permitir análise tática, dividindo-se em duas fases de banimento e duas fases de escolha de acordo com o posicionamento (**Blue Side** à esquerda, inicia o draft; **Red Side** à direita, tem o counter pick).

### Fase 1: Primeiros Bans e Picks (3 Bans e 3 Picks por lado)
1.  **Bans Iniciais (Alternados):** Blue Bane 1 ➔ Red Bane 1 ➔ Blue Bane 2 ➔ Red Bane 2 ➔ Blue Bane 3 ➔ Red Bane 3.
2.  **Picks Iniciais (Formato 1-2-2-1):**
    * **Blue Side** escolhe 1º Campeão (B1)
    * **Red Side** escolhe 1º e 2º Campeões (R1, R2)
    * **Blue Side** escolhe 2º e 3º Campeões (B2, B3)
    * **Red Side** escolhe 3º Campeão (R3)

### Fase 2: Bans e Picks Finais (2 Bans e 2 Picks por lado)
3.  **Bans Finais (Alternados):** Red Bane 4 ➔ Blue Bane 4 ➔ Red Bane 5 ➔ Blue Bane 5. (Nota: O Red Side inicia banindo na segunda fase).
4.  **Picks Finais (Formato 1-1-1-1):**
    * **Red Side** escolhe 4º Campeão (R4)
    * **Blue Side** escolhe 4º e 5º Campeões (B4, B5)
    * **Red Side** escolhe 5º Campeão (R5)

---

## 4. Regra de Bloqueio de Campeões na Série (Formato Fearless)

Para trazer dinamismo e estratégia realística às séries de MD3 ou MD5, aplica-se a regra de eliminação acumulativa de campeões:
* **Mecânica de Bloqueio:** Os campeões que forem **escolhidos (picks)** no Jogo 1 são automaticamente salvos e **bloqueados** para o Jogo 2 e para o eventual Jogo 3 (na MD3), ou Jogos 2, 3, 4 e 5 (na MD5).
* **Independência de Resultado:** Não importa qual time ganhou ou perdeu a partida anterior; se o campeão entrou em campo no mapa anterior por qualquer um dos lados, ele fica cinza e indisponível para seleção nas partidas seguintes daquela mesma série.
* **Persistência por Jogo:** O sistema deve armazenar em arrays separados ou tabelas relacionais os campeões usados em cada ID de partida para computar os bloqueios nos jogos subsequentes.

---

## 5. Histórico e Tela de Detalhes Pós-Draft

A aplicação deve disponibilizar um painel de gerenciamento e revisão da série com as seguintes regras de navegação:
* **Visualização da Série:** Uma tela centralizada que mostra o andamento da série (ex: Jogo 1, Jogo 2, Jogo 3).
* **Click para Detalhar:** Quando o usuário clica em um jogo específico que já teve seu draft finalizado, a aplicação abre uma visualização detalhada ou modal expandido.
* **Exibição dos Detalhes:** Esta tela exibe exatamente como o draft terminou de forma estática:
    * Todos os 5 campeões banidos de cada lado (com ordem indicada).
    * Todos os 5 campeões selecionados para as composições de cada lado.
    * Quais daqueles campeões selecionados haviam sido marcados no sistema de **Prioridade** antes do início do jogo, exibindo um selo ou indicador exclusivo de "Prioridade Escolhida".

---

## 6. Mapeamento de Estrutura de Código para o Anti-Gravity

O Anti-Gravity deve gerar os componentes seguindo esta lógica:
* `Champion` (Model): Contém `id`, `name`, `role`, `image_url`.
* `Series` (Model): Contém o tipo (`bo3` ou `bo5`) e o status da série.
* `GameMatch` (Model): Vinculado a `Series`. Armazena chaves estrangeiras ou arrays JSON de `blue_bans`, `red_bans`, `blue_picks`, `red_picks`, e `priorities_selected`.
* `DraftSimulator` (Componente Livewire): Controla a máquina de estados do turno ativo (`$current_turn`) de forma sequencial no backend, validando se o campeão clicado não está na lista de banidos do jogo atual E nem na lista de escolhidos de jogos anteriores daquela série.
