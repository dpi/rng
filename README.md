RNG is a Drupal module enabling people to register for events.

Copyright (C) 2016 Daniel Phin (@dpi)

[![Build Status](https://travis-ci.org/dpi/rng.svg?branch=8.x-1.x)](https://travis-ci.org/dpi/rng)

# License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

# About

RNG is inspired by contributed Registration and Signup modules. Development
originally began early 2013, but has been reworked due to inactivity of the
original project, and unexpected extension of development timeline for Drupal 8.

See MAINTAINERS.txt for a list of official developers.

# Dependencies

__RNG__

 *  [Dynamic Entity Reference](https://www.drupal.org/project/dynamic_entity_reference)
 *  [Courier](https://www.drupal.org/project/courier)
 *  [Unlimited Number](https://www.drupal.org/project/unlimited_number)

__RNG Views sub module__

 *  [Views Entity Operation Access](https://www.drupal.org/project/veoa)
 *  [Views Advanced Routing](https://www.drupal.org/project/views_advanced_routing)

# Terms

 *  __Event__: any content entity.
 *  __Registration type__: bundle entity for Registrations.
 *  __Registration__: an entity that associates with one Event, and has at least
    one child Registrant. Each Registration has at least one owner Registrant.
 *  __Registrant__: an entity that maintains a relationship between a
    Registration and an Identity.
 *  __Identity__: an entity that has implemented a method for contact. Cores'
    user module provides the User entity. Identity module provides the Contact
    entity, allowing users to create registrations by providing an email
    address.
 *  __EventType__: and entity maintaining configuration, and default
    values for EventConfig. Each EventType is associated with another
    entity/bundle combination.

# Model

    ┌─ Event Type
    ├─ Registration Type(s)
    └─►Event ─┬─► Registration(s) ─┬─► Registrant(s) ─► Identity
              ├────────────────────┴─► Group(s)
              └─► Rule(s) ─┬─► Action(s)
                           └─► Condition(s)

A Registration is a content entity that associated with an Event entity, and 
maintains relationships to Identities via Registrant entities. Each Registrant
maintains a relationship between a Registration and an Identity. Registrant 
entities are content, and thus can hold meta information in fields describing 
how an Identity relates to a Registration.

# Usage

Please see the project websites for instructions:

 *  https://www.drupal.org/project/rng
 *  https://github.com/dpi/rng
 *  http://dpi.id.au/rng-quick-start/

# Building

## SASS

```sh
sass --watch sass:css --style expanded
```
