# Installation Guide

## Requirements

### System Requirements

- **CiviCRM**: 6.1 or higher
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **CMS**: WordPress, Drupal, or Joomla

### Required Extensions

- **CiviRules** (org.civicoop.civirules) - Required for automation

### Recommended Extensions

- **Action Provider** - Enhanced FormProcessor integration
- **FormProcessor** - Advanced form actions
- **SearchKit** - Enhanced reporting (CiviCRM 6.1+ core)

## Installation Methods

### Method 1: Extension Manager (Recommended)

1. In CiviCRM, go to **Administer → System Settings → Extensions**
2. Click **Add New** tab
3. Search for "mascode"
4. Click **Download** then **Install**

### Method 2: Manual Installation

```bash
# Download latest release
cd /path/to/civicrm/extensions/
wget https://github.com/briangflett/mascode/releases/latest/download/mascode.tar.gz
tar -xzf mascode.tar.gz

# Enable via CiviCRM
cv ext:enable mascode
```
