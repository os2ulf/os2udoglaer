# OS2UOL Pretix Module

## Overview
The **OS2UOL Pretix** module provides integration between Drupal and Pretix, an open-source online ticket sales solution. This module allows users to synchronize event data, manage orders and handle webhook events from Pretix directly in a Drupal environment.

### Features
- Integration with Pretix API to manage event orders and ticketing.
- Support for Pretix webhooks for real-time synchronization of orders and events.
- Custom Drupal forms to manage Pretix settings and sub-events.
- Role-based permissions for managing Pretix integrations within Drupal.

## Requirements
- Drupal 10 or later.
- PHP 8.2 or later.
- Access to Pretix API (requires API token and organizer details).
- Composer dependencies, as defined in the `composer.json` file of the module (ensure you run `composer install`).

## Installation
1. Place the `os2uol_pretix` module directory in your Drupal installation under `/modules/custom/`.
2. Run `composer install` to install any necessary dependencies.
3. Enable the module using Drupal's UI or via Drush:
   ```bash
   drush en os2uol_pretix -y
   ```

## Configuration
1. **Pretix API Settings**:
   - Go to **Configuration** -> **Pretix Settings**.
   - Fill in the required fields such as **API Token** and **Organizer**.
2. **Webhook Setup**:
   - The module automatically registers a Pretix webhook with the Pretix API.
   - Ensure that your Drupal site is accessible from Pretix for webhook communication.
3. **User Roles and Permissions**:
   - Assign permissions as required using the **Permissions** page. Relevant permissions include managing Pretix settings and accessing event data.

## Usage
- **Managing Events and Orders**: Events are managed via Pretix, and this module pulls that information into Drupal for administrative purposes.
- **Webhook Handling**: The module supports various webhook events, such as new order placement, cancellations, reactivations, refunds, etc. These events are automatically processed and logged in Drupal.

## Developer Notes
### Module Structure
- **Controllers**: The controllers (`PretixController.php`, `WebhooksController.php`) handle user interactions and incoming webhooks.
- **Services**: The core logic for interacting with Pretix API is encapsulated in service classes such as `PretixClient.php` and `PretixConnector.php`.
- **Forms**: Configuration forms (`PretixSettingsForm.php`, `PretixSubEventAddForm.php`, etc.) handle the UI for managing module settings.
- **Routing**: The `os2uol_pretix.routing.yml` defines custom routes for accessing the module's functionality.

### Webhook Events
The following webhook events are currently handled by this module:
- `pretix.event.order.placed`
- `pretix.event.order.canceled`
- `pretix.event.order.reactivated`
- `pretix.event.order.refund.created`

Webhook URLs are automatically registered, but you can manually review and manage these from your Pretix organizer settings.

## Troubleshooting
- Ensure that **API Token** and **Organizer** fields are correctly configured under **Pretix Settings**.
- If the webhook does not trigger properly, confirm that the Drupal site is accessible externally.
- Check logs in Drupal's **Recent log messages** (`admin/reports/dblog`) for error messages related to `os2uol_pretix`.

## Contributing
Contributions are welcome! Please ensure your code adheres to Drupal's coding standards and includes appropriate documentation.

1. Fork the repository.
2. Make your changes in a feature branch.
3. Create a pull request detailing the changes made.

## License
This module is open-source and licensed under the **GNU General Public License, version 2 (GPL-2.0)**.

## Contact
For any questions or support requests, please contact the development team at [php@novicell.dk].

