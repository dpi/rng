RNG is a Drupal module for allowing a person to associate with an event.

RNG is inspired by contributed Registration and Signup modules. Development
originally began early 2013, but has been reworked due to inactivity of the
original project, and unexpected extension of development timeline for Drupal 8.

See MAINTAINERS.txt for a list of official developers.
See LICENSE.txt for information on how RNG is licensed. License will change at a
later date.

# Terms

* Host: any entity. A host entity represents an event.
* Registration: an entity that associates with one Host entity, and has at least
  one child Registrant. Each Registration has at least one owner Registrant.
* Registrant: an entity that maintains a relationship between a Registration and
  a Contact.
* Contact: any entity that has implemented a method for contacting itself, core
  implements the User entity, although RNG provides another entity which is used
  for anonymous purposes.

# Model

Host -> Registration(s) -> Registrant(s) -> Contact

A Registration is a fieldable entity that is associated with a Host entity,
and maintains relationships to Contacts via Registrant entities.
Each Registrant holds the relationship between one Registration and one Contact.
Registrant entities are fieldable, and thus can hold meta information about how
a contact relates to a Registration.