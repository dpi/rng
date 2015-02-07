RNG is a Drupal module for allowing a person to associate with an event.

RNG is inspired by contributed Registration and Signup modules. Development
originally began early 2013, but has been reworked due to inactivity of the
original project, and unexpected extension of development timeline for Drupal 8.

See MAINTAINERS.txt for a list of official developers.
See LICENSE.txt for information on how RNG is licensed. License will change at a
later date.

# Terms

 * Event: any content (fieldable) entity.
 * Registration type: bundle entity for Registrations.
 * Registration: an entity that associates with one Event, and has at least
   one child Registrant. Each Registration has at least one owner Registrant.
 * Registrant: an entity that maintains a relationship between a Registration
   and an Identity.
 * Identity: any entity that has implemented a method for contact, core 
   implements the User entity, although RNG provides another entity which is
   used for anonymous purposes.
 * EventTypeConfig: and entity maintaining configuration, and default values
   for EventConfig. Each EventTypeConfig is associated with an event bundle.
   This type exists pending [#2361775].

# Model

Event -> Registration(s) -> Registrant(s) -> Identity

A Registration is a fieldable entity that is associated with an Event entity,
and maintains relationships to Identities via Registrant entities.
Each Registrant holds the relationship between one Registration and one 
Identity. Registrant entities are fieldable, and thus can hold meta information
about how an Identity relates to a Registration.