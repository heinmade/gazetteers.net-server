// stub, not implemented

var defaultLanguage = "en";

function getIntText(textid, lang){
  if(intTexts.hasOwnProperty(textid)){
    var prop = intTexts[textid];
    if(prop.hasOwnProperty(lang)){
      return prop[lang];
    }
    else if(prop.hasOwnProperty(defaultLanguage)){
      return prop[defaultLanguage];
    }
  }
  return "";
}

var intTexts = {
  "gov_partofgraph": {
    "en": "Created by gov.genealogy.net",
    "de": "Erzeugt von gov.genealogy.net"
  },
  // General search tooltip
  "tt_search": {
    "en": "Search for name and optionally a geographic region (via a boundingbox)"
  },
  // "Databases"
  "tt_gazetteers": {
    "en": "Select the gazetteers to be queried.<br>If possible, live querying of a gazetteer is used (via API calls), possible for Geonames, GOV, GND. The other gazetteers' data is queried via downloaded database dumps."
  },
  // "Options"
  "tt_options": {
    "en": "Search options"
  },
  // "Original search"
  "tt_options_namesearch_org": {
    "en": "Use the specific default name search behavior of each gazetteer"
  },
  // "Match whole name"
  "tt_options_namesearch_on_name": {
    "en": "Search string must match an entity's name (e.g.: search for 'Wrocław' does not match 'Wrocław-Bartoszewice')"
  },
  // "Match word in name"
  "tt_options_namesearch_on_word": {
    "en": "Search string must match a word in an entity's name (e.g.: search for 'Wrocław' does also match 'Wrocław-Bartoszewice')"
  }
}

