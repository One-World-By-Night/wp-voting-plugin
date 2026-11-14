# Modelos de cargos e classificações

Listas de cargos salvas para preencher o acesso de uma votação com um clique, e as classificações em que as votações são arquivadas.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **WP Voting** → **Templates**, e depois **Role Templates** ou **Classifications**.

## A tela

**Role Templates**

- **Add New Role Template**.
- Uma linha por modelo: **Name**, **Description**, **Roles** e **Created**, com **Edit** e **Delete**.
- Um modelo tem um **Template Name**, uma **Description** e **Roles / Groups**: caminhos de cargo, papéis do WordPress ou padrões, um por linha, como `chronicle/*/cm`.

**Classifications**

- **Add New Classification**.
- Uma linha por classificação: o nome e a **Display Order**, com **Edit** e **Delete**.
- Números menores vêm primeiro na lista **Proposal Type** do editor de votações.

## Tarefas comuns

### Criar um modelo de cargos

1. Clique em **Add New Role Template**.
2. Dê um nome, como "All Chronicle CMs", e liste os cargos.
3. Clique em **Create Template**.

No editor de votações, escolha-o em **Load from template** e clique em **Apply**.

### Acrescentar uma classificação

1. Em **Classifications**, clique em **Add New Classification**.
2. Dê um nome e uma **Display Order**, e clique em **Save Classification**.

## Bom saber

- **Apply** copia os cargos do modelo para a votação. Mudar o modelo depois não muda as votações que o usaram.
- Num caminho de cargo, `*` vale por uma parte qualquer e `**` por qualquer número de partes.

## Solução de problemas

- **"Failed to create template. The name may already be in use."** Escolha outro nome.
- **"At least one role is required."** Acrescente um cargo ao modelo.

## Relacionados

- [Criar e editar uma votação](vote-editor.md)
- [Configurações das votações](settings.md)
