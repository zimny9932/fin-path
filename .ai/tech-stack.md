Frontend - Astro z React dla komponentów interaktywnych:
- Astro 5 pozwala na tworzenie szybkich, wydajnych stron i aplikacji z minimalną ilością JavaScript
- React 19 zapewni interaktywność tam, gdzie jest potrzebna
- TypeScript 5 dla statycznego typowania kodu i lepszego wsparcia IDE
- Tailwind 4 pozwala na wygodne stylowanie aplikacji
- Shadcn/ui zapewnia bibliotekę dostępnych komponentów React, na których oprzemy UI

Backend - Docker + PHP 8.4 + Symfony 7.3 + PostgresSql 17:
- Jest rozwiązaniem open source, które można hostować lokalnie lub na własnym serwerze
- Ma szerokie community oraz bogatą dokumentację

CI/CD i Hosting:
- Gitlab pipelines do tworzenia pipeline’ów CI/CD
- DigitalOcean do hostowania aplikacji za pośrednictwem obrazu docker

Testowanie:
- Testy należy uruchamiać w kontenerze dockerowym za pomocą polecenia: `docker-compose exec -T php bin/phpunit`
- Projekt wykorzystuje `dama/doctrine-test-bundle`, który automatycznie zarządza transakcjami testowymi. Dzięki temu nie ma potrzeby ręcznego czyszczenia bazy danych w metodach `tearDown`.
- W testach API (`ApiTestCase`) należy ustawić `static::$alwaysBootKernel = false;` w metodzie `setUp()`, aby uniknąć ostrzeżeń o "deprecation" związanych z przyszłymi zmianami w API Platform.