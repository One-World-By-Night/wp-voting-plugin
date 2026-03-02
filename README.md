> **Current Version**: 3.10.8 (Production Release - March 2026)
> **Multisite Support**: Enabled with per-site data isolation
> **Previous Version**: 1.x (Archived as `wp-voting-plugin-v1-legacy-20260209.tar.gz`)
> **Version History**: See [VERSION_HISTORY.md](VERSION_HISTORY.md) for complete changelog and migration guide.
>
> ### Recent Changes (3.10.8)
> - Consent agenda auto-pass — close date syncs to open date; passes by silence on cron
> - Objection converts consent to FPTP with 7-day window, renames [AUTOPASS] to [OBJECTION]
>
> ### Recent Changes (3.10.7)
> - Fix: Vote-closed email now includes winner data (race condition fixed)
> - Fix: Multi-winner elections display all winners in email
> - Fix: Tie and partial-win scenarios display correctly

# Requirements Specification Document for WordPress Voting Plugin

## 1. Introduction

This document outlines the requirements for a WordPress plugin designed to facilitate and manage various types of voting systems within a WordPress site. The plugin will leverage WordPress's Role-Based Access Control (RBAC) to ensure appropriate access levels, and support multiple voting mechanisms including ranked choice, first past the post, simple majority, super-majority, and 2/3rds majority. It will allow for setting open and close dates for voting and display detailed vote data upon closure.

## 2. Objective

The objective of this plugin is to provide a flexible, secure, and user-friendly voting system that can be integrated into WordPress websites, enabling site administrators to conduct votes on various matters with ease and precision.

## 3. Functional Requirements

### 3.1 General Requirements

* **Compatibility**: The plugin must be compatible with the latest version of WordPress and maintain compatibility with future updates.
* **User Interface**: Provide an intuitive admin interface and user experience, aligning with WordPress's admin UI guidelines.

### 3.2 Voting Creation and Management

* **RBAC Integration**: Utilize WordPress's existing RBAC system to control who can create, manage, and participate in votes.
* **Voting Types Support**: Must support the following voting mechanisms:
    * Ranked Choice Voting
    * First Past the Post
    * Simple Majority
    * Super-Majority
    * Two-Thirds Majority
* **Custom Voting Types**: Allow for the creation of custom voting rules as needed.
* **Voting Period Configuration**: Enable administrators to set specific open and close dates and times for each vote.
* **Voter Eligibility**: Administrators can define eligibility criteria based on user roles.

### 3.3 Voting Process

* **Anonymity Option**: Provide the option to make votes anonymous.
* **Live Tracking**: Offer real-time vote counting without revealing results until the voting period ends.
* **Accessibility**: Ensure the voting interface is accessible according to WCAG standards.
* **Vote Changes**: All votes can be changed or edited during the voting period by the voter alone.

### 3.4 Results Display and Management

* **Results Computation**: Automatically calculate and display results based on the voting type used.
    * For ranked choice voting, include an algorithm to calculate the winner according to ranked preferences.
* **Results Display**: Show vote totals, percentages, and, for ranked choice, the computation process upon vote closure.
* **Historical Data**: Store and allow administrators to view past votes and results.

### 3.5 Security and Integrity

* **Data Validation**: Ensure all input data, including votes and configurations, are validated to prevent injection attacks.
* **Audit Trail**: Maintain a log of all actions related to voting for auditing purposes.

## 4. Non-functional Requirements

### 4.1 Performance

* **Load Handling**: The plugin should not significantly impact the site's performance, even under heavy voting traffic.

### 4.2 Security

* **Data Protection**: Implement strong data protection measures to safeguard vote integrity and user privacy.
* **WordPress Security Standards**: Adhere to WordPress's security best practices and guidelines.

### 4.3 Scalability

* The plugin must be scalable, capable of handling a growing number of votes and participants with minimal performance degradation.

## 5. Documentation and Support

* **User Documentation**: Provide comprehensive user documentation, including installation, configuration, and usage instructions.
* **Developer Documentation**: Offer developer documentation for extending and customizing the plugin.
* **Support**: Establish a support channel for addressing user questions and issues.

## 6. Testing Requirements

* **Unit Testing**: Perform unit testing on all core functionalities to ensure reliability.
* **Integration Testing**: Conduct integration testing with WordPress to verify plugin compatibility and functionality.
* **Accessibility Testing**: Test the voting interface against WCAG standards to ensure accessibility.

## 7. Compliance

* The plugin must comply with all relevant laws and regulations regarding online voting and data protection, including GDPR where applicable.

## 8. Delivery

* The plugin should be packaged and delivered as a standard WordPress plugin installable through the WordPress admin interface or via FTP.
* Developer will be allowed to distribute the completed plug-in through the WordPress Plugin space with development credit shared with One World by Night.

## Requirements Verification Traceability Matrix

A Requirements Verification Traceability Matrix (RVTM) provides a structured approach to ensuring that each functional and non-functional requirement outlined in the Requirements Specification Document is verified through appropriate testing methods. Below is an RVTM for the WordPress Voting Plugin based on the provided requirements.

| Req ID | Requirement Description | Test Case ID | Test Method | Expected Outcome |
| ------ | ----------------------- | ------------ | ----------- | ---------------- |
| FR1.1 | Plugin compatibility with the latest version of WordPress. | TC1 | Compatibility Test | The plugin installs successfully and functions correctly with the latest WordPress version. |
| FR2.1 | Integration with WordPress RBAC for vote creation and management. | TC2 | Functional Test | Only users with specified roles can create/manage votes. |
| FR2.2 | Support for ranked choice, first past the post, simple majority, super-majority, and 2/3rds majority voting. | TC3 | Functional Test | The plugin allows creation of votes with each specified voting mechanism and processes votes accordingly. |
| FR2.3 | Ability to set specific open and close dates for each vote. | TC4 | Functional Test | Administrators can set and edit open/close dates, and the system enforces these times. |
| FR2.4 | Configuration of voter eligibility based on user roles. | TC5 | Functional Test | Only users with roles designated as eligible can participate in the vote. |
| FR3.1 | Option to make votes anonymous. | TC6 | Functional Test | Votes can be configured as anonymous, and voter identities are not disclosed with results. |
| FR3.2 | Real-time vote counting with results hidden until voting closes. | TC7 | Functional Test | Real-time counting is verified, and results remain hidden until the voting period ends. |
| FR3.3 | Voting interface accessibility according to WCAG standards. | TC8 | Accessibility Test | The voting interface meets WCAG standards for accessibility. |
| FR3.4 | Votes can be changed. | TC9 | Functional Test | All votes can be changed or edited during the voting period by the voter alone. |
| FR4.1 | Automatic calculation and display of results based on voting type. | TC10 | Functional Test | Results are correctly calculated for each voting type and displayed after vote closure. |
| FR4.2 | Storage and accessibility of historical vote data. | TC11 | Functional Test | Past votes and results are stored and can be accessed by administrators. |
| NFR1.1 | Plugin performance under heavy voting traffic. | TC12 | Performance Test | The plugin maintains performance benchmarks under simulated heavy voting traffic. |
| NFR2.1 | Protection of vote integrity and user privacy. | TC13 | Security Test | Data protection measures prevent unauthorized access and ensure vote and user data integrity. |
| NFR3.1 | Plugin scalability with an increasing number of votes and participants. | TC14 | Scalability Test | The plugin handles a scaling number of votes and users without significant degradation in performance. |
| DOC1.1 | Availability and comprehensiveness of user documentation. | TC15 | Documentation Review | User documentation covers installation, configuration, and usage instructions clearly. |
| DOC2.1 | Availability of developer documentation for customization. | TC16 | Documentation Review | Developer documentation provides clear guidelines for extending and customizing the plugin. |
| COM1.1 | Compliance with GDPR and other relevant regulations. | TC17 | Compliance Review | The plugin's data handling and privacy practices comply with GDPR and other applicable laws. |

Each test case (TC) is designed to verify the corresponding requirement. The test method column specifies whether the verification will be conducted through functional testing, compatibility testing, performance testing, security testing, accessibility testing, scalability testing, documentation review, or compliance review. The expected outcome provides a clear benchmark for what constitutes a successful test, enabling testers to mark each test as a pass or fail accordingly. Notes can include any additional observations or details relevant to the execution or outcome of the test.

---

## 1. Introducao

Este documento descreve os requisitos para um plugin WordPress projetado para facilitar e gerenciar varios tipos de sistemas de votacao em um site WordPress. O plug-in aproveitara o controle de acesso baseado em funcao (RBAC) do WordPress para garantir niveis de acesso apropriados e oferecer suporte a varios mecanismos de votacao, incluindo escolha classificada, primeiro apos a postagem, maioria simples, supermaioria e maioria de 2/3. Isso permitira definir datas de abertura e encerramento para votacao e exibir dados detalhados de votacao apos o encerramento.

## 2. Objetivo

O objetivo deste plugin e fornecer um sistema de votacao flexivel, seguro e facil de usar que pode ser integrado a sites WordPress, permitindo que os administradores do site conduzam votacoes em diversos assuntos com facilidade e precisao.

## 3. Requisitos Funcionais

### 3.1 Requisitos Gerais

* Compatibilidade: O plugin deve ser compativel com a versao mais recente do WordPress e manter compatibilidade com atualizacoes futuras.
* Interface do usuario: fornece uma interface de administracao e experiencia de usuario intuitivas, alinhando-se as diretrizes de interface de administracao do WordPress.

### 3.2 Criacao e Gestao de Votos

* Integracao RBAC: Utilize o sistema RBAC existente do WordPress para controlar quem pode criar, gerenciar e participar de votacoes.
* Suporte a tipos de votacao: Deve suportar os seguintes mecanismos de votacao:
    * Votacao de escolha classificada
    * Primeiro apos o post
    * Maioria simples
    * Supermaioria
    * Maioria de dois tercos
* Tipos de votacao personalizados: permitem a criacao de regras de votacao personalizadas conforme necessario.
* Configuracao do periodo de votacao: permite que os administradores definam datas e horarios especificos de abertura e fechamento para cada votacao.
* Elegibilidade do eleitor: os administradores podem definir criterios de elegibilidade com base nas funcoes do usuario.

### 3.3 Processo de Votacao

* Opcao de anonimato: fornece a opcao de tornar os votos anonimos.
* Acompanhamento ao vivo: oferece contagem de votos em tempo real sem revelar resultados ate o final do periodo de votacao.
* Acessibilidade: Certifique-se de que a interface de votacao esteja acessivel de acordo com os padroes WCAG.
* Mudancas de Voto: Todos os votos podem ser alterados ou editados durante o periodo de votacao apenas pelo eleitor.

### 3.4 Exibicao e Gerenciamento de Resultados

* Calculo de resultados: calcule e exiba automaticamente os resultados com base no tipo de votacao utilizado.
* Para votacao por escolha classificada, inclua um algoritmo para calcular o vencedor de acordo com as preferencias classificadas.
* Exibicao de resultados: mostra totais de votos, porcentagens e, para escolha de classificacao, o processo de calculo no encerramento da votacao.
* Dados historicos: armazene e permita que os administradores visualizem votos e resultados anteriores.

### 3.5 Seguranca e Integridade

* Validacao de dados: Garanta que todos os dados de entrada, incluindo votos e configuracoes, sejam validados para evitar ataques de injecao.
* Trilha de Auditoria: Mantenha um registro de todas as acoes relacionadas a votacao para fins de auditoria.

## 4. Requisitos Nao Funcionais

### 4.1 Desempenho

* Tratamento de carga: O plugin nao deve impactar significativamente o desempenho do site, mesmo sob trafego intenso de votacao.

### 4.2 Seguranca

* Protecao de Dados: Implemente medidas fortes de protecao de dados para salvaguardar a integridade do voto e a privacidade do usuario.
* Padroes de seguranca do WordPress: siga as melhores praticas e diretrizes de seguranca do WordPress.

### 4.3 Escalabilidade

* O plugin deve ser escalavel, capaz de lidar com um numero crescente de votos e participantes com degradacao minima de desempenho.

## 5. Documentacao e Suporte

* Documentacao do usuario: forneca documentacao abrangente do usuario, incluindo instrucoes de instalacao, configuracao e uso.
* Documentacao do desenvolvedor: oferece documentacao do desenvolvedor para estender e personalizar o plugin.
* Suporte: estabeleca um canal de suporte para responder as duvidas e problemas dos usuarios.

## 6. Requisitos de teste

* Teste de unidade: execute testes de unidade em todas as funcionalidades principais para garantir a confiabilidade.
* Teste de integracao: realize testes de integracao com WordPress para verificar a compatibilidade e funcionalidade do plugin.
* Teste de acessibilidade: teste a interface de votacao em relacao aos padroes WCAG para garantir a acessibilidade.

## 7. Conformidade

* O plug-in deve cumprir todas as leis e regulamentos relevantes relativos a votacao online e protecao de dados, incluindo o GDPR, quando aplicavel.

## 8. Entrega

* O plugin deve ser empacotado e entregue como um plugin padrao do WordPress, instalavel atraves da interface de administracao do WordPress ou via FTP.
* O desenvolvedor tera permissao para distribuir o plug-in completo atraves do espaco de plug-in do WordPress com credito de desenvolvimento compartilhado com One World by Night.
