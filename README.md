# Gerador de código comportamental.
A ferramenta é capaz de gerar código comportamental a partir de um modelo aberto de descrição de serviços.

## Instalação e execução

Clone o projeto em sua máquina.

Execute o comando no diretório onde se encontra o projeto clonado.
```bash
composer install
```
Para gerar o código, execute o comando.
```bash
php artisan generate:code caminho_da_sua_documentação_api_baixada
```

Copie o .env.example do projeto, e salve-o com o nome .env e então configure com os dados do seu banco.
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1 (caso seja ambiemte de produção, troque o host)
DB_PORT=3306 (caso troque a porta do banco, altera aqui)
DB_DATABASE=nome_da_sua_database_aqui
DB_USERNAME=username_do_banco_aqui
DB_PASSWORD=senha_do_banco_aqui (caso não houver, deixe em branco)
```

Após a configuração execute o comando abaixo para criar as tabelas no banco.
```bash
php artisan migrate
```

Para executar o aplicativo em desenvolvimento, você pode executar este comando.
```bash
php -S 127.0.0.1:8000 -t public/
```

## Utilização da documentação OpenAPI

Os modelos e migrations da api, são gerados através do campo **_components_** e sua estrutura é da seguinte forma
~~~yml
components:
  schemas:
    Event:
      required:
        - title 
        - description 
      type: object
      properties:
        title:
          type: string
        description:
          type: string
        participants:
          type: integer
          default: 0
~~~

* **schemas**: É utilizado na ferramenta para pegar quais serão os modelos da api.
* **Event**: É o nome do modelo.
* **required**: Um array que recebe quais são os atributos obrigatório daquele modelo, os que não são inseridos, são considerados não obrigatórios.
* **properties**: são os atributos que aquele modelo vai possuir, cada um tem seu _nome_ e _tipo(type)_. 
* **default**: é utilizado para a criação da migration desse modelo, caso o atributo tenha um valor default, esse valor será inserido na migration.

Todo o comportamento é inserido no campo **_description_** de cada método HTTP, que possui a seguinte estrutura.
~~~yml
post:
  summary: Cadastro de eventos
  description: |
    Criar um novo evento

    <dsl>
      Model(Event)->post();
      Return('Evento criado com sucesso', 200)
    <dsl>
  operationId: addEvent
  requestBody:
    content:
      application/json:
        schema:
          $ref: '#/components/schemas/Event'
    required: true
  responses:
    "200":
      description: OK
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/Event'
~~~
* **post**: É o método http utilizado para aquele determinado caminho.
* **summary**: Uma descrição curta da api.
* **description**: A descrição da api, e também onde será inserido o comportamento dela.
* **operationId**: É um id unico que será utilizado como nome do método em questão. 
* **requestBody**: É o conteúdo que será utilizado para inserção dos dados (este campo só é utilizados para os métodos post, put e patch).
* **responses**: É todo o contéudo que esse método pode retornar

## Utilização da DSL

Todo comportamento é inserido aqui

~~~yml
description: |
    Criar um novo evento

    <dsl>
      Model(Event)->post();
      Return('Evento criado com sucesso', 200)
    <dsl>
~~~

### Manipulação de dados: Model(ModelName)->httpMethod();
    1. O parâmetro ModelName, é o nome do modelo que será utilizado para manipulação dos dados;
    2. O httpMethod é o método http que será utilizado para essa manipulação (post, put, get, patch e delete);
    3. Exemplos:
        + Inserção de dados: Model(Event)->post();
        + Obtenção de um dado: Model(Event)->get()->first();
        + Obtenção de dados: Model(Event)->get()->all();
        + Atualizados de dados: Model(Event)->put(&id);
        + Atualizados de dados: Model(Event)->patch(&id);
        + Remoção de dados: Model(Event)->delete(&id);
        + Obs: o caractere '&', significa que será utilizado o parâmetro que foi inserido no path, é necessário colocar o mesmo nome que foi inserido
### Atribuição de dados: $event = (...);
    1. A variavel precisa ser iniciada com "$" para determinar que aquilo é uma variável do sistema.
    2. Ex: $event = Model(Event)->get()->first();
### Retorno de dados: Return(content, httpCode);
    1. O parâmetro content, é o conteúdo que será retornado pela api seja ele uma string ou um recurso qualquer;
    2. O httpCode é o código de retorno.
    3. Ex: Return("Evento criado com sucesso", 200);
