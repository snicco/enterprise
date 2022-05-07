## How to get logged in status

### Check for cookie presence of `LOGGED_IN_COOKIE`:

- We can't validate the cookie, anybody could present himself as logged in.
- Presenting himself as logged in is not a big problem as a user could only disable plugins for himself

## How to get user roles

- Store in cookie on log in.
- Remove cookie on logout.

## How to get the user object

- store id on log in?


## Other ideas

- Custom pluggable auth functions. (Bad because if somebody else overwrites auth functions we won't know it. They would also have to overwrite our auth functions.)
- Encrypted cookie
- Store a random hash in a cookie and a user id in the user meta.

## => paragonie double select pattern

## Define dependencies between plugins

1. A deactivation of one "mother" plugin should auto disable all children.