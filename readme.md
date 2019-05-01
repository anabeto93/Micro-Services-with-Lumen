# Lumen SOA / Microservices

This is a simple project developed and modified as a proof of concept of how to implement microservices with Laravel/Lumen.

Credits of this project actually goes to [Indranil Samanta](https://github.com/code-architect) for the initial work done.
Details of the initial project will be found at [Microservices with Lumen](https://github.com/code-architect/Microservices-with-Lumen)

 
 The Main system is __ApiGateway__ and the microservices are __AuthorApi__ & __BookApi__.
 I am using guzzle for consuming api and dusterio/lumen-passport for security.
 
Also stopping direct access to the microservices, to access the microservies directly the client must pass some kind of token which is registered in the microservice. 


 