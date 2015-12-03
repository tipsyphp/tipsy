#!/bin/sh

phpunit --tap --configuration tests/phpunit.xml --coverage-text
