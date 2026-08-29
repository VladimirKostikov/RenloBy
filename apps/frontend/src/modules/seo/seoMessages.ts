import type { SeoLocale } from './types'

type SeoMessageTree = {
  home: { title: string; description: string; h1: string; keywords: string }
  rentCatalog: { title: string; description: string; h1: string; keywords: string }
  saleCatalog: { title: string; description: string; h1: string; keywords: string }
  commercialCatalog: { title: string; description: string; h1: string; keywords: string }
  searchMap: { title: string; description: string; h1: string; keywords: string }
  login: { title: string; description: string }
  favorites: { title: string; description: string }
  compare: { title: string; description: string }
  promotion: { title: string; description: string }
  admin: { title: string; description: string }
  listing: {
    titleSale: string
    titleRent: string
    description: string
  }
  location: {
    cityTitle: string
    cityDescription: string
    districtTitle: string
    districtDescription: string
    regionTitle: string
    regionDescription: string
  }
  info: {
    description: string
  }
  articles: {
    title: string
    description: string
    h1: string
    itemDescription: string
  }
  organization: {
    name: string
    description: string
  }
}

const ru: SeoMessageTree = {
  home: {
    title: 'Renlo - недвижимость в Беларуси',
    description: 'Покупка, продажа и аренда квартир в Беларуси. Каталог объявлений, карта и фильтры по городам и районам.',
    h1: 'Недвижимость в Беларуси',
    keywords: 'недвижимость Беларусь, квартиры Минск, аренда квартир, купить квартиру, Renlo',
  },
  rentCatalog: {
    title: 'Аренда квартир в Беларуси - Renlo',
    description: 'Объявления об аренде квартир, домов и комнат в Беларуси. Фильтры по цене, району и метро.',
    h1: 'Аренда недвижимости',
    keywords: 'аренда квартир, снять квартиру Беларусь, аренда жилья Минск',
  },
  saleCatalog: {
    title: 'Продажа квартир в Беларуси - Renlo',
    description: 'Объявления о продаже квартир, домов и комнат в Беларуси. Актуальные цены и карта объектов.',
    h1: 'Продажа недвижимости',
    keywords: 'продажа квартир, купить квартиру Беларусь, недвижимость Минск',
  },
  commercialCatalog: {
    title: 'Коммерческая недвижимость в Беларуси - Renlo',
    description: 'Объявления о продаже и аренде коммерческой недвижимости в Беларуси. Офисы, склады и торговые помещения.',
    h1: 'Коммерческая недвижимость',
    keywords: 'коммерческая недвижимость, офис в аренду, склад Беларусь',
  },
  searchMap: {
    title: 'Карта объявлений - Renlo',
    description: 'Поиск недвижимости на карте Беларуси. Фильтры по типу сделки, цене и району.',
    h1: 'Карта объявлений',
    keywords: 'карта недвижимости, объявления на карте, поиск квартир Беларусь',
  },
  login: {
    title: 'Вход - Renlo',
    description: 'Вход в личный кабинет Renlo.',
  },
  favorites: {
    title: 'Избранное - Renlo',
    description: 'Сохранённые объявления Renlo.',
  },
  compare: {
    title: 'Сравнение - Renlo',
    description: 'Сравнение объявлений Renlo.',
  },
  promotion: {
    title: 'Продвижение объявлений - Renlo',
    description: 'Тарифы продвижения объявлений на Renlo.',
  },
  admin: {
    title: 'Админка - Renlo',
    description: 'Панель администратора Renlo.',
  },
  listing: {
    titleSale: '{roomsLabel} квартира, {area} м², {district} - {price} $ | Renlo',
    titleRent: '{roomsLabel} квартира в аренду, {area} м², {district} - {price} $/мес | Renlo',
    description: '{dealType} {roomsLabel} {listingType}, {area} м², {address}, {district}, {city}. Цена {price}.',
  },
  location: {
    cityTitle: 'Недвижимость в {city} - Renlo',
    cityDescription: 'Купить или снять квартиру в {city}. Объявления о продаже и аренде на Renlo.',
    districtTitle: 'Недвижимость в районе {district}, {city} - Renlo',
    districtDescription: 'Объявления о продаже и аренде в районе {district}, {city}.',
    regionTitle: 'Недвижимость в {region} - Renlo',
    regionDescription: 'Купить или снять квартиру в {region}. Объявления о продаже и аренде на Renlo.',
  },
  info: {
    description: '{title}. Полезная информация о недвижимости на Renlo.',
  },
  articles: {
    title: 'Статьи о недвижимости - Renlo',
    description: 'Статьи и гайды о покупке, продаже и аренде жилья в Беларуси.',
    h1: 'Статьи',
    itemDescription: '{title}. Статья о недвижимости на Renlo.',
  },
  organization: {
    name: 'Renlo',
    description: 'Агрегатор покупки, продажи и аренды квартир в Беларуси.',
  },
}

const en: SeoMessageTree = {
  home: {
    title: 'Renlo - real estate in Belarus',
    description: 'Buy, sell and rent apartments in Belarus. Listings catalog, map and filters by city and district.',
    h1: 'Real estate in Belarus',
    keywords: 'real estate Belarus, apartments Minsk, rent apartment, buy apartment, Renlo',
  },
  rentCatalog: {
    title: 'Apartments for rent in Belarus - Renlo',
    description: 'Rent listings for apartments, houses and rooms in Belarus. Filters by price, district and metro.',
    h1: 'Rent listings',
    keywords: 'apartments for rent, rent apartment Belarus, housing rent Minsk',
  },
  saleCatalog: {
    title: 'Apartments for sale in Belarus - Renlo',
    description: 'Sale listings for apartments, houses and rooms in Belarus. Current prices and map view.',
    h1: 'Sale listings',
    keywords: 'apartments for sale, buy apartment Belarus, real estate Minsk',
  },
  commercialCatalog: {
    title: 'Commercial real estate in Belarus - Renlo',
    description: 'Commercial property listings in Belarus. Offices, warehouses and retail spaces.',
    h1: 'Commercial listings',
    keywords: 'commercial real estate, office for rent, warehouse Belarus',
  },
  searchMap: {
    title: 'Listings map - Renlo',
    description: 'Search real estate on the map of Belarus. Filters by deal type, price and district.',
    h1: 'Listings map',
    keywords: 'real estate map, listings map, apartment search Belarus',
  },
  login: {
    title: 'Sign in - Renlo',
    description: 'Sign in to your Renlo account.',
  },
  favorites: {
    title: 'Favorites - Renlo',
    description: 'Your saved Renlo listings.',
  },
  compare: {
    title: 'Compare - Renlo',
    description: 'Compare Renlo listings.',
  },
  promotion: {
    title: 'Listing promotion - Renlo',
    description: 'Renlo listing promotion plans.',
  },
  admin: {
    title: 'Admin - Renlo',
    description: 'Renlo admin panel.',
  },
  listing: {
    titleSale: '{roomsLabel} apartment, {area} m², {district} - {price} $ | Renlo',
    titleRent: '{roomsLabel} apartment for rent, {area} m², {district} - {price} $/mo | Renlo',
    description: '{dealType} {roomsLabel} {listingType}, {area} m², {address}, {district}, {city}. Price {price}.',
  },
  location: {
    cityTitle: 'Real estate in {city} - Renlo',
    cityDescription: 'Buy or rent an apartment in {city}. Sale and rent listings on Renlo.',
    districtTitle: 'Real estate in {district}, {city} - Renlo',
    districtDescription: 'Sale and rent listings in {district}, {city}.',
    regionTitle: 'Real estate in {region} - Renlo',
    regionDescription: 'Buy or rent an apartment in {region}. Sale and rent listings on Renlo.',
  },
  info: {
    description: '{title}. Real estate guides on Renlo.',
  },
  articles: {
    title: 'Real estate articles - Renlo',
    description: 'Guides and articles about buying, selling and renting homes in Belarus.',
    h1: 'Articles',
    itemDescription: '{title}. Real estate article on Renlo.',
  },
  organization: {
    name: 'Renlo',
    description: 'Apartment buy, sell and rent aggregator in Belarus.',
  },
}

const messages: Record<SeoLocale, SeoMessageTree> = { ru, en }

export function getSeoMessages(locale: SeoLocale): SeoMessageTree {
  return messages[locale]
}

export function fillSeoTemplate(template: string, params: Record<string, string | number>): string {
  return template.replace(/\{(\w+)\}/g, (_, key: string) => String(params[key] ?? ''))
}
