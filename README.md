> **Current Version**: 3.4.0 (Production Release - February 2026)
> **Multisite Support**: Enabled with per-site data isolation
> **Previous Version**: 1.x (Archived as `wp-voting-plugin-v1-legacy-20260209.tar.gz`)
> **Version History**: See [VERSION_HISTORY.md](VERSION_HISTORY.md) for complete changelog and migration guide.
>
> ### Recent Changes (3.4.0)
> - Non-blind votes now visible to non-logged-in visitors (results transparency)
> - Live results accessible to guests when "Show results while voting is open" is enabled
> - Blind votes retain existing visibility rules

<h1>Requirements Specification Document for<br />
WordPress Voting Plugin</h1>

<h2>1. Introduction</h2>


This document outlines the requirements for a WordPress plugin designed to facilitate and manage various types of voting systems within a WordPress site. The plugin will leverage WordPress's Role-Based Access Control (RBAC) to ensure appropriate access levels, and support multiple voting mechanisms including ranked choice, first past the post, simple majority, super-majority, and 2/3rds majority. It will allow for setting open and close dates for voting and display detailed vote data upon closure.

<h2>2. Objective</h2>


The objective of this plugin is to provide a flexible, secure, and user-friendly voting system that can be integrated into WordPress websites, enabling site administrators to conduct votes on various matters with ease and precision.

<h2>3. Functional Requirements</h2>


<h3>3.1 General Requirements</h3>




* **Compatibility**: The plugin must be compatible with the latest version of WordPress and maintain compatibility with future updates.
* **User Interface**: Provide an intuitive admin interface and user experience, aligning with WordPress's admin UI guidelines.

<h3>3.2 Voting Creation and Management</h3>




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

<h3>3.3 Voting Process</h3>




* **Anonymity Option**: Provide the option to make votes anonymous.
* **Live Tracking**: Offer real-time vote counting without revealing results until the voting period ends.
* **Accessibility**: Ensure the voting interface is accessible according to WCAG standards.
* **Vote Changes: **All votes can be changed or edited during the voting period by the voter alone.

<h3>3.4 Results Display and Management</h3>




* **Results Computation**: Automatically calculate and display results based on the voting type used.
    * For ranked choice voting, include an algorithm to calculate the winner according to ranked preferences.
* **Results Display**: Show vote totals, percentages, and, for ranked choice, the computation process upon vote closure.
* **Historical Data**: Store and allow administrators to view past votes and results.

<h3>3.5 Security and Integrity</h3>




* **Data Validation**: Ensure all input data, including votes and configurations, are validated to prevent injection attacks.
* **Audit Trail**: Maintain a log of all actions related to voting for auditing purposes.

<h2>4. Non-functional Requirements</h2>


<h3>4.1 Performance</h3>




* **Load Handling**: The plugin should not significantly impact the site's performance, even under heavy voting traffic.

<h3>4.2 Security</h3>




* **Data Protection**: Implement strong data protection measures to safeguard vote integrity and user privacy.
* **WordPress Security Standards**: Adhere to WordPress's security best practices and guidelines.

<h3>4.3 Scalability</h3>




* The plugin must be scalable, capable of handling a growing number of votes and participants with minimal performance degradation.

<h2>5. Documentation and Support</h2>




* **User Documentation**: Provide comprehensive user documentation, including installation, configuration, and usage instructions.
* **Developer Documentation**: Offer developer documentation for extending and customizing the plugin.
* **Support**: Establish a support channel for addressing user questions and issues.

<h2>6. Testing Requirements</h2>




* **Unit Testing**: Perform unit testing on all core functionalities to ensure reliability.
* **Integration Testing**: Conduct integration testing with WordPress to verify plugin compatibility and functionality.
* **Accessibility Testing**: Test the voting interface against WCAG standards to ensure accessibility.

<h2>7. Compliance</h2>




* The plugin must comply with all relevant laws and regulations regarding online voting and data protection, including GDPR where applicable.

<h2>8. Delivery</h2>




* The plugin should be packaged and delivered as a standard WordPress plugin installable through the WordPress admin interface or via FTP.
* Developer will be allowed to distribute the completed plug-in through the WordPress Plugin space with development credit shared with One World by Night.



<h2>Requirements Verification Traceability Matrix</h2>


A Requirements Verification Traceability Matrix (RVTM) provides a structured approach to ensuring that each functional and non-functional requirement outlined in the Requirements Specification Document is verified through appropriate testing methods. Below is an RVTM for the WordPress Voting Plugin based on the provided requirements.

 


<table>
  <tr>
   <td><strong>Req ID</strong>
   </td>
   <td><strong>Requirement Description</strong>
   </td>
   <td><strong>Test Case ID</strong>
   </td>
   <td><strong>Test Method</strong>
   </td>
   <td><strong>Expected Outcome</strong>
   </td>
  </tr>
  <tr>
   <td> FR1.1         
   </td>
   <td> Plugin compatibility with the latest version of WordPress.                                                      
   </td>
   <td> TC1         
   </td>
   <td> Compatibility Test
   </td>
   <td> The plugin installs successfully and functions correctly with the latest WordPress version.               
   </td>
  </tr>
  <tr>
   <td> FR2.1         
   </td>
   <td> Integration with WordPress RBAC for vote creation and management.                                               
   </td>
   <td> TC2         
   </td>
   <td> Functional Test   
   </td>
   <td> Only users with specified roles can create/manage votes.                                                  
   </td>
  </tr>
  <tr>
   <td> FR2.2         
   </td>
   <td> Support for ranked choice, first past the post, simple majority, super-majority, and 2/3rds majority voting.   
   </td>
   <td> TC3         
   </td>
   <td> Functional Test   
   </td>
   <td> The plugin allows creation of votes with each specified voting mechanism and processes votes accordingly. 
   </td>
  </tr>
  <tr>
   <td> FR2.3         
   </td>
   <td> Ability to set specific open and close dates for each vote.                                                     
   </td>
   <td> TC4         
   </td>
   <td> Functional Test   
   </td>
   <td> Administrators can set and edit open/close dates, and the system enforces these times.                    
   </td>
  </tr>
  <tr>
   <td> FR2.4         
   </td>
   <td> Configuration of voter eligibility based on user roles.                                                         
   </td>
   <td> TC5         
   </td>
   <td> Functional Test   
   </td>
   <td> Only users with roles designated as eligible can participate in the vote.                                 
   </td>
  </tr>
  <tr>
   <td> FR3.1         
   </td>
   <td> Option to make votes anonymous.                                                                                 
   </td>
   <td> TC6         
   </td>
   <td> Functional Test   
   </td>
   <td> Votes can be configured as anonymous, and voter identities are not disclosed with results.                
   </td>
  </tr>
  <tr>
   <td> FR3.2         
   </td>
   <td> Real-time vote counting with results hidden until voting closes.                                                
   </td>
   <td> TC7         
   </td>
   <td> Functional Test   
   </td>
   <td> Real-time counting is verified, and results remain hidden until the voting period ends.                   
   </td>
  </tr>
  <tr>
   <td> FR3.3         
   </td>
   <td> Voting interface accessibility according to WCAG standards.                                                     
   </td>
   <td> TC8         
   </td>
   <td> Accessibility Test
   </td>
   <td> The voting interface meets WCAG standards for accessibility.                                              
   </td>
  </tr>
  <tr>
   <td>FR3.4
   </td>
   <td>Voets can be changed
   </td>
   <td>TC9
   </td>
   <td>Functional Test
   </td>
   <td>All votes can be changed or edited during the voting period by the voter alone
   </td>
  </tr>
  <tr>
   <td> FR4.1         
   </td>
   <td> Automatic calculation and display of results based on voting type.                                              
   </td>
   <td> TC10         
   </td>
   <td> Functional Test   
   </td>
   <td> Results are correctly calculated for each voting type and displayed after vote closure.                   
   </td>
  </tr>
  <tr>
   <td> FR4.2         
   </td>
   <td> Storage and accessibility of historical vote data.                                                              
   </td>
   <td> TC11        
   </td>
   <td> Functional Test   
   </td>
   <td> Past votes and results are stored and can be accessed by administrators.                                 
   </td>
  </tr>
  <tr>
   <td> NFR1.1        
   </td>
   <td> Plugin performance under heavy voting traffic.                                                                  
   </td>
   <td> TC12        
   </td>
   <td> Performance Test  
   </td>
   <td> The plugin maintains performance benchmarks under simulated heavy voting traffic.                         
   </td>
  </tr>
  <tr>
   <td> NFR2.1        
   </td>
   <td> Protection of vote integrity and user privacy.                                                                  
   </td>
   <td> TC13        
   </td>
   <td> Security Test     
   </td>
   <td> Data protection measures prevent unauthorized access and ensure vote and user data integrity.             
   </td>
  </tr>
  <tr>
   <td> NFR3.1        
   </td>
   <td> Plugin scalability with an increasing number of votes and participants.                                         
   </td>
   <td> TC14        
   </td>
   <td> Scalability Test  
   </td>
   <td> The plugin handles a scaling number of votes and users without significant degradation in performance.    
   </td>
  </tr>
  <tr>
   <td> DOC1.1        
   </td>
   <td> Availability and comprehensiveness of user documentation.                                                       
   </td>
   <td> TC15        
   </td>
   <td> Documentation Review
   </td>
   <td> User documentation covers installation, configuration, and usage instructions clearly.                   
   </td>
  </tr>
  <tr>
   <td> DOC2.1        
   </td>
   <td> Availability of developer documentation for customization.                                                      
   </td>
   <td> TC16        
   </td>
   <td> Documentation Review
   </td>
   <td> Developer documentation provides clear guidelines for extending and customizing the plugin.              
   </td>
  </tr>
  <tr>
   <td> COM1.1        
   </td>
   <td> Compliance with GDPR and other relevant regulations.                                                            
   </td>
   <td> TC17        
   </td>
   <td> Compliance Review  
   </td>
   <td> The plugin's data handling and privacy practices comply with GDPR and other applicable laws.              
   </td>
  </tr>
</table>


 

Each test case (TC) is designed to verify the corresponding requirement. The test method column specifies whether the verification will be conducted through functional testing, compatibility testing, performance testing, security testing, accessibility testing, scalability testing, documentation review, or compliance review. The expected outcome provides a clear benchmark for what constitutes a successful test, enabling testers to mark each test as a pass or fail accordingly. Notes can include any additional observations or details relevant to the execution or outcome of the test.

<h2>1. Introdução</h2>


Este documento descreve os requisitos para um plugin WordPress projetado para facilitar e gerenciar vários tipos de sistemas de votação em um site WordPress. O plug-in aproveitará o controle de acesso baseado em função (RBAC) do WordPress para garantir níveis de acesso apropriados e oferecer suporte a vários mecanismos de votação, incluindo escolha classificada, primeiro após a postagem, maioria simples, supermaioria e maioria de 2/3. Isso permitirá definir datas de abertura e encerramento para votação e exibir dados detalhados de votação após o encerramento.

<h2>2. Objetivo</h2>


O objetivo deste plugin é fornecer um sistema de votação flexível, seguro e fácil de usar que pode ser integrado a sites WordPress, permitindo que os administradores do site conduzam votações em diversos assuntos com facilidade e precisão.

<h2>3. Requisitos Funcionais</h2>


<h3>3.1 Requisitos Gerais</h3>




* Compatibilidade: O plugin deve ser compatível com a versão mais recente do WordPress e manter compatibilidade com atualizações futuras.
* Interface do usuário: fornece uma interface de administração e experiência de usuário intuitivas, alinhando-se às diretrizes de interface de administração do WordPress.

<h3>3.2 Criação e Gestão de Votos</h3>




* Integração RBAC: Utilize o sistema RBAC existente do WordPress para controlar quem pode criar, gerenciar e participar de votações.
* Suporte a tipos de votação: Deve suportar os seguintes mecanismos de votação:
    * Votação de escolha classificada
    * Primeiro após o post
    * Maioria simples
    * Supermaioria
    * Maioria de dois terços
* Tipos de votação personalizados: permitem a criação de regras de votação personalizadas conforme necessário.
* Configuração do período de votação: permite que os administradores definam datas e horários específicos de abertura e fechamento para cada votação.
* Elegibilidade do eleitor: os administradores podem definir critérios de elegibilidade com base nas funções do usuário.

<h3>3.3 Processo de Votação</h3>




* Opção de anonimato: fornece a opção de tornar os votos anônimos.
* Acompanhamento ao vivo: oferece contagem de votos em tempo real sem revelar resultados até o final do período de votação.
* Acessibilidade: Certifique-se de que a interface de votação esteja acessível de acordo com os padrões WCAG.
* Mudanças de Voto: Todos os votos podem ser alterados ou editados durante o período de votação apenas pelo eleitor.

<h3>3.4 Exibição e Gerenciamento de Resultados</h3>




* Cálculo de resultados: calcule e exiba automaticamente os resultados com base no tipo de votação utilizado.
* Para votação por escolha classificada, inclua um algoritmo para calcular o vencedor de acordo com as preferências classificadas.
* Exibição de resultados: mostra totais de votos, porcentagens e, para escolha de classificação, o processo de cálculo no encerramento da votação.
* Dados históricos: armazene e permita que os administradores visualizem votos e resultados anteriores.

<h3>3.5 Segurança e Integridade</h3>




* Validação de dados: Garanta que todos os dados de entrada, incluindo votos e configurações, sejam validados para evitar ataques de injeção.
* Trilha de Auditoria: Mantenha um registro de todas as ações relacionadas à votação para fins de auditoria.

<h2>4. Requisitos Não Funcionais</h2>


<h3>4.1 Desempenho</h3>




* Tratamento de carga: O plugin não deve impactar significativamente o desempenho do site, mesmo sob tráfego intenso de votação.

<h3>4.2 Segurança</h3>




* Proteção de Dados: Implemente medidas fortes de proteção de dados para salvaguardar a integridade do voto e a privacidade do usuário.
* Padrões de segurança do WordPress: siga as melhores práticas e diretrizes de segurança do WordPress.

<h3>4.3 Escalabilidade</h3>




* plugin deve ser escalável, capaz de lidar com um número crescente de votos e participantes com degradação mínima de desempenho.

<h2>5. Documentação e Suporte</h2>




* Documentação do usuário: forneça documentação abrangente do usuário, incluindo instruções de instalação, configuração e uso.
* Documentação do desenvolvedor: oferece documentação do desenvolvedor para estender e personalizar o plugin.
* Suporte: estabeleça um canal de suporte para responder às dúvidas e problemas dos usuários.

<h2>6. Requisitos de teste</h2>




* Teste de unidade: execute testes de unidade em todas as funcionalidades principais para garantir a confiabilidade.
* Teste de integração: realize testes de integração com WordPress para verificar a compatibilidade e funcionalidade do plugin.
* Teste de acessibilidade: teste a interface de votação em relação aos padrões WCAG para garantir a acessibilidade.

<h2>7. Conformidade</h2>




* O plug-in deve cumprir todas as leis e regulamentos relevantes relativos à votação online e proteção de dados, incluindo o GDPR, quando aplicável.

<h2>8. Entrega</h2>




* O plugin deve ser empacotado e entregue como um plugin padrão do WordPress, instalável através da interface de administração do WordPress ou via FTP.
* O desenvolvedor terá permissão para distribuir o plug-in completo através do espaço de plug-in do WordPress com crédito de desenvolvimento compartilhado com One World by Night.
