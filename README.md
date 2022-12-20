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

## Utilização da DSL na documentação OpenAPI

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

Todo o comportamento da api é inserido no campo **_description_** de cada método HTTP.

Todo método HTTP do openAPI possui a seguinte estrutura

~~~yml
/events:
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
