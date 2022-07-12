# WordPress Files

The files in inside the .wp/html directory are copied using `docker cp`
each time `make dev-server` is run.

They **DO NOT** reflect changes in real-time, and they are not mapped as a volume
to the docker container. Changing these files has no effect on the docker container.

They are meant to be used as development helpers for IDE auto-completion 
and Xdebug support.

