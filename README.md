# Ook

A translator for XML/JSON files using a YAML or JSON configuration.

### What is "Ook"?

In the *Discworld* series by Larry Niven, the Librarian is a wizard turned into an orangutan early in the series. He understands Morporkian (English) perfectly well, but always speaks in orangutan, using words like "Ook" and "Eek". Most human characters have little trouble understanding this, but every now and again someone unfamiliar with the Librarian meets him and cannot quite figure out what that particular "Ook" meant. The Librarian tried to address this issue by writing an Orangutan-Morpokian dictionary, but has not progressed beyond "Ook" yet.

### Oh, I see.

Yeah, pretty funny, right?

## Quick Start

### Define a configuration

Ook utilizes the "dot" notation when defining rules for translating between arrays. Here's a quick example of what dot notation is:

```php

$array = ['items' => [
    'item' => [
        ['name' => 'computer', 'price' => 1000],
        ['name' => 'monitor', 'price' => 150]
    ]
];

Arr::get('items.item.0.price') // 1000
```

We'll use the same thing to define a ruleset for XML:

#### sample.xml
```xml
<items>
    <item>
        <name>computer</name>
        <price>1000</price>
    </item>
    <item>
        <name>monitor</name>
        <price>150</price>
    </item>
</items>

```

Ook will convert this to an array with a key of `item.price`. Let's imagine we want to translate this from `item.price` to `inventory.item.price`

#### config.yaml
```yaml
inventory.item.*.name: items.item.*.name
inventory.item.*.price: items.item.*.price
```

To translate it, we simply run:

```php

$librarian = new Ook\Librarian('sample.xml', 'config.yaml');
$output = $librarian->transform();
```

This will return:

```
Array
(
    [inventory] => Array
        (
            [item] => Array
                (
                    [0] => Array
                        (
                            [name] => computer
                            [price] => 1000
                        )

                    [1] => Array
                        (
                            [name] => monitor
                            [price] => 150
                        )

                )

        )

)
```

### Additional Details
Check out the `/examples` directory for more samples.

