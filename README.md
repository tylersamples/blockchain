# Blockchain

A very simplistic demonstration of how a Blockchain in Symfony would look. Far from complete.

## Setup

Install deps from composer
```
$ composer install
```
Initialize and optionally modify your env variables.
```
$ cat > .env << EOL
# Docker env variables.
NGINX_PORT=8081
REDIS_HOST=redis://redis
# Symfony specifc variables.
APP_ENV=prod
APP_SECRET=31d134503efcfeb3257364bf3df9913e
EOL
```

Run the demo Docker setup
```
$ ./demo.sh
```

### Demo
Add Genesis Block
```
$ curl -iX POST -d $(php -r 'echo json_encode(["id" => 0, "previousHash" => "", "data" => "PHP Meetup Demo"]);') $(docker-machine ip peer1)/block
```
Add Peers
```
$ curl -iX POST -d $(php -r 'echo json_encode(["peers" => ["'$(docker-machine ip peer2)'", "'$(docker-machine ip peer3)'", "not.accessible"]]);') $(docker-machine ip peer1)/peer
```
