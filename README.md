# Inclusive AI Descriptions
A WordPress plugin that generates accessible image descriptions for people with visual impairments using OpenAI's GPT-4 vision capabilities. This plugin was specifically created for [Fotografia USAL](https://fotografiadiscapacidad.usal.es/) to enhance accessibility in photography contests focused on disability awareness.

## Features
- 🤖 AI-Powered Descriptions: Uses OpenAI's GPT-4 to generate detailed, context-aware image descriptions
- ♿ Accessibility Focus: Specifically trained to identify and describe various disabilities and accessibility features
- 🖼️ Multiple Format Support: Works with PNG, JPEG, JPG, GIF, and WebP images
- ⚡ Easy Integration: Seamlessly integrates with WordPress admin and ACF fields
- 🔄 Real-time Processing: Generates descriptions instantly with a simple click

# How It Works
The plugin analyzes images and generates comprehensive descriptions that include:

- Detailed visual descriptions of the image content
- Disability identification (physical, intellectual, Down Syndrome, auditory, visual, cerebral palsy, deafblindness, etc.)
- Context recognition (work, sports, leisure, health, education, accessibility, etc.)
- Accessibility features and adaptations
- Portrait analysis and facial/body characteristics

## Installation
### Method 1: Download from Releases
1. Download the latest version from the releases page
2. Upload the plugin folder to /wp-content/plugins/
3. Activate the plugin through the 'Plugins' menu in WordPress

### Method 2: Manual Installation
1. Clone this repository into your /wp-content/plugins/ directory
2. Activate the plugin in your WordPress admin panel

## Requirements
- WordPress 5.0 or higher
- Advanced Custom Fields (ACF) plugin
- Custom post type 'fotografia'
- ACF field named 'descripcion_ia'
- OpenAI API key
- PHP 7.4 or higher

## Configuration
1. Set up OpenAI API Key:
    - Replace the API key in InclusiveAiDescriptions.php with your own
    - Currently using GPT-4 model
2. Required ACF Field:
    - Create an ACF field with the name descripcion_ia
    - This field should be attached to your 'fotografia' post type

## Supported Image Formats
- PNG
- JPEG
- JPG
- GIF
- WebP

## Privacy & Ethics
- The plugin sends images to OpenAI's API for processing
- No personal data is stored permanently
- Descriptions are generated with dignity and respect for individuals with disabilities
- Images of explicit nature are handled with appropriate clothing assumptions

## Troubleshooting
### Button not appearing?

- Verify the ACF field is named like in the code
- Check that you're on the correct post type
- Ensure the plugin is activated

### Description generation failing?

- Verify featured image is set
- Check internet connection
- Confirm OpenAI API key is valid
- Wait a few minutes and retry if API is temporarily unavailable

## Contributing
Contributions are welcome! Please feel free to submit pull requests or open issues for bugs and feature requests.

## License
This project is licensed under the GPL v2 or later.

## Support
For support and questions, please open an issue in this repository.
***
*Making visual content accessible for everyone through AI-powered descriptions.*
