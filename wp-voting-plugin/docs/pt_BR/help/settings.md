# Configurações das votações

Os padrões do site para as votações novas, os destinatários dos emails, como os cargos dos votantes são conferidos e as ferramentas para apurar votações atrasadas.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **WP Voting** → **Settings** (a tela se chama **WP Voting Settings**), e depois a aba **General**, **Permissions** ou **Advanced**.

## A tela

**General**

- **Default Voting Type** - o tipo com que uma votação nova começa.
- **Require Login to Vote**.
- **Show Results Before Close** - deixa os votantes habilitados verem a contagem enquanto a votação está aberta.
- **Email Notifications** - manda os emails de abertura, de lembrete e de fechamento. Os votantes também podem pedir a confirmação da própria cédula.
- **Admin Notification Email**, e os destinatários padrão de **Vote Opened Recipients**, **Closing Reminder Recipients** e **Vote Closed Recipients**. Em branco usa os destinatários de sempre, mostrados ao lado de cada um.

**Permissions**

- **AccessSchema Connection** - **Connection Mode** (**Disabled**, **Remote Server** ou **Local (same site)**), **Remote URL**, **API Key** e **Test Connection**. Enquanto ela está configurada, os cargos de cada membro em sso.owbn.net decidem o que ele vê e em que vota.
- **WordPress Capability Fallback** - os recursos do WordPress para **Create Votes**, **Manage Votes**, **Cast Votes** e **View Results**, usados só quando o AccessSchema não está configurado.
- **Default Access Control for New Votes** - **Default Visibility**, **Default Visibility Roles**, **Default Voting Eligibility** e **Default Voting Roles**, postos em toda votação nova.

**Advanced**

- **Vote Processing** - **Process All Closed Votes** apura qualquer votação fechada que ainda está sem resultado.
- **Migration from v1 Plugin** - **Check for v1 Data** e **Run Migration**, para sites que usaram o plugin antigo.
- **Remove Data on Uninstall** - apaga todas as votações, cédulas e resultados se o plugin for desinstalado.

**Save Changes** salva a aba.

## Tarefas comuns

### Conferir a conexão com o AccessSchema

1. Em **Permissions**, clique em **Test Connection**.
2. "Connection successful." quer dizer que o endereço e a chave funcionam.

### Apurar uma votação que fechou sem resultado

1. Em **Advanced**, clique em **Process All Closed Votes**.

## Bom saber

- Com o AccessSchema configurado, um membro sem cargos registrados aqui não vê as votações restritas nem vota. Ele recebe os cargos ao entrar.
- Os padrões só preenchem as votações novas; as que já existem mantêm as próprias configurações.
- Deixe **Remove Data on Uninstall** desligado.

## Solução de problemas

- **Test Connection falha.** Confira **Remote URL** e **API Key** com as configurações do accessSchema em sso.owbn.net.
- **Ninguém recebe email.** Confira se **Email Notifications** está ligado e se os destinatários estão certos. Se estiverem, o email do site não está saindo; fale com a equipe de Web.

## Relacionados

- [Criar e editar uma votação](vote-editor.md)
- [Modelos de cargos e classificações](templates.md)
- [Solução de problemas das votações](troubleshooting.md)
