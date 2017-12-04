#!/bin/sh

if [ ! $(which ngrok) ]; then
  echo "ngrok is not installed in this system."
  echo "Please install it and try again. See https://ngrok.com/download"
  exit 1
fi

ngrok start aodev
