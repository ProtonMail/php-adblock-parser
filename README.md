PHP parser for Adblock Plus filters
===================================

[![Master Build Status](https://github.com/ProtonMail/php-adblock-parser/actions/workflows/master.yml/badge.svg)](https://github.com/ProtonMail/php-adblock-parser/actions/workflows/master.yml)

This is a fork of the abandoned Limonte\AdblockParser.

It has been edited to optimize performance, namely:
- The object containing the rules can be saved to avoid the long time to create it.
- Only run necessary rules; the generic rules and the rules applying specifically to the domain of the url.

It also separates responsibilities of classes better by having factories, services and DTOs.

Usage
-----

To learn about Adblock Plus filter syntax check these links:

- https://adblockplus.org/en/filter-cheatsheet
- https://adblockplus.org/en/filters

1. Get filter rules somewhere: write them manually, read lines from a file
   downloaded from [EasyList](https://easylist.to/), etc.:

   ```php
   $adblockEntries = [
       "||ads.example.com^",
       "@@||ads.example.com/notbanner^$~script",
   ];
   ```

2. Create AdblockRules instance from the rules array:

   ```php
   use Limonte\AdblockParser;

   $ruleAggregate = (new RuleAggregateFactory(new RuleFactory()))->createFromAdblockEntries($adblockEntries);
   ```

3. Use this instance to check if an URL should be blocked or not:

   ```php
   (new RuleApplier())->shouldBlock("http://ads.example.com", $ruleAggregate); // true
   (new RuleApplier())->shouldBlock("http://non-ads.example.com", $ruleAggregate); // false
   ```

Related projects
----------------

- Google Safebrowsing PHP library: [limonte/google-safebrowsing](https://github.com/limonte/google-safebrowsing)
- McAfee SiteAdvisor PHP library: [limonte/mcafee-siteadvisor](https://github.com/limonte/mcafee-siteadvisor)
- Check if link is SPAM: [limonte/spam-link-analyser](https://github.com/limonte/spam-link-analyser)

---

- Python parser for Adblock Plus filters: [scrapinghub/adblockparser](https://github.com/scrapinghub/adblockparser/)
- EasyList filter subscription: [easylist/easylist](https://github.com/easylist/easylist/)
