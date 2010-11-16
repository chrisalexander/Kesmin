Kesmin is a PHP-based simple interface available for the web and command line to access various pieces of data that are available from Kestrel but not normally easily readable.

Currently rendering in a web browser is only supported, the command line interface is coming soon.

This data includes:

* per-server statistics
* which queues are currently loaded on a server
* which queues are currently loaded on a cluster
* stats about a queue on a server
* per-cluster statistics
* cluster-wide stats about a queue

Actions available include:

* get from a queue
* peek from a queue
* flush a queue
* delete a queue
* add entry to a queue
* flush all queues
* delete all queues
* flush a queue's fanout queues
* delete a queue's fanout queues
* get an item from a queue for each server in a cluster
* peek an item from a queue for each server in a cluster
* flush a queue on all servers in a cluster
* delete a queue on all servers in a cluster
* flush all queues in a cluster
* delete all queues in a cluster