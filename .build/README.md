# The .build directory

All ci artifacts are generated in this directory which has
a bind-mount in CI.

This directory must exist in order for the bind-mount to work.
Otherwise, we will get all sorts of weird docker permission errors.