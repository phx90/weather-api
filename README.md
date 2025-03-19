# Weather API

Uma API de clima simples e RESTful construída com o Laravel Lumen. Esta API utiliza a API do Open‑Meteo para obter dados climáticos e a API de Geocodificação do Open‑Meteo para converter nomes de cidades em coordenadas.

--------------------------------------------------

## Funcionalidades

- **Clima Atual:** Retorna temperatura, umidade, descrição, ícone e horário atual.
- **Frase Diária:** Gera uma frase resumindo o clima atual.
- **Previsão para 7 Dias:** Fornece previsão dos próximos 7 dias (temperaturas máxima e mínima, descrição, ícone, horários de nascer e pôr do sol).
- **Média de Temperatura de Ontem:** Calcula a média da temperatura do dia anterior.
- **Conversão de Temperatura:** Converte temperatura entre Celsius, Fahrenheit e Kelvin.
- **Nascer e Pôr do Sol:** Retorna os horários de nascer e pôr do sol para a data atual.
- **Previsão de Chuva:** Indica se há previsão de chuva nos próximos 7 dias.
- **Comparação de Temperatura:** Compara a temperatura atual com a média de ontem.

--------------------------------------------------

## Pré-requisitos

- PHP 8.1 ou superior
- Composer
- Git

--------------------------------------------------

## Instalação

1. **Clone o repositório:**
   git clone https://github.com/seu-usuario/weather-api.git
   cd weather-api

2. **Instale as dependências:**
   composer install

3. **Configure as variáveis de ambiente:**
   - Copie o arquivo de exemplo e renomeie para .env:
     cp .env.example .env
   - Edite o arquivo .env e defina as seguintes variáveis:

     APP_NAME=WeatherAPI
     APP_ENV=local
     APP_DEBUG=true
     APP_URL=http://localhost:8000

     API_BASE_URL=https://api.open-meteo.com/v1/forecast
     DEFAULT_CITY=Brasilia
     DEFAULT_LATITUDE=-15.7801
     DEFAULT_LONGITUDE=-47.9292

4. **(Opcional) Configure o CA Bundle do PHP:**

   Caso ocorram erros de SSL, baixe o arquivo de certificados do link oficial:

   https://curl.se/ca/cacert.pem

   Em seguida, no arquivo php.ini, descomente e ajuste as diretivas:

   curl.cainfo = "C:\php\extras\ssl\cacert.pem"
   openssl.cafile = "C:\php\extras\ssl\cacert.pem"

--------------------------------------------------

## Execução da API:

Para iniciar o servidor embutido do PHP, execute:
   php -S localhost:8000 -t public

A API estará disponível em:
   http://localhost:8000

--------------------------------------------------

## Endpoints:

As rotas estão agrupadas com o prefixo /api/weather. Exemplos:

**- Clima Atual:** GET /api/weather/current?city=Brasilia

**- Frase Diária:** GET /api/weather/daily-phrase?city=Los Angeles

**- Previsão para 7 Dias:** GET /api/weather/forecast?city=Chicago

**- Média de Temperatura de Ontem:** GET /api/weather/yesterday-average?city=Miami

**- Conversão de Temperatura:** GET /api/weather/convert?temperature=25&unit=F

**- Nascer e Pôr do Sol:** GET /api/weather/sunrise-sunset?city=San Francisco

**- Previsão de Chuva:** GET /api/weather/rain-forecast?city=Seattle

**- Comparação de Temperatura:** GET /api/weather/compare?city=Boston

--------------------------------------------------

## Testes:

A API inclui testes unitários e de integração. Para executar os testes, use:

   vendor/bin/phpunit

Certifique-se de que o arquivo tests/TestCase.php esteja configurado para carregar a aplicação, por exemplo:

<?php
namespace Tests;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
abstract class TestCase extends BaseTestCase {
    public function createApplication() {
        return require __DIR__.'/../bootstrap/app.php';
    }
}


--------------------------------------------------

## Contribuição:

Sinta-se à vontade para enviar pull requests, relatar issues ou sugerir melhorias.

--------------------------------------------------

## Licença:
Este projeto está licenciado sob a MIT License.