# Roadiz Document base system

[![Build Status](https://travis-ci.org/roadiz/documents.svg?branch=master)](https://travis-ci.org/roadiz/documents)

## HTML templates

You can override and inherit from document rendering templates by creating them in your theme at the same
path inside your `views/` folder.

### VueJS and <noscript>

You may need to override `<noscript>` block to add `inline-template` attribute :

```twig
{% block noscript_attributes %} inline-template{% endblock %}
```

Do not forget to add a leading space before your attributes.
