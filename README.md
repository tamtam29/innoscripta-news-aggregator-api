# innoscripta - News Aggregator API

A Laravel-based news aggregation system that provides unified access to multiple news providers (NewsAPI, Guardian, New York Times) with caching, background processing, and preferences.

[![Backend](https://img.shields.io/badge/Backend-Laravel%2012-red?style=for-the-badge&logo=laravel)](backend/)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-13+-4169E1?style=for-the-badge&logo=postgresql)
[![Queue System](https://img.shields.io/badge/Queue-Redis%20%2B%20Laravel-green?style=for-the-badge&logo=redis)](backend/)
[![Docker](https://img.shields.io/badge/Docker-Containerized-blue?style=for-the-badge&logo=docker)](docker-compose.yml)

## 🚀 Features

- **Multi-Provider Integration**: Seamlessly aggregates news from NewsAPI, Guardian, and New York Times
- **Caching**: Cache invalidation with configurable freshness intervals
- **Background Processing**: Queue-based article fetching for optimal performance
- **User Preferences**: Personalized filtering by sources, categories, and authors
- **RESTful API**: Complete OpenAPI 3.0 documented endpoints
- **Docker Support**: Full containerization with PostgreSQL and Nginx
- **Repository Pattern**: Clean architecture with dependency injection
- **Testing**: Unit and feature tests for reliability

## 🏗️ Architecture Overview

### Design Patterns

This application follows **SOLID principles** and implements several design patterns:

- **Repository Pattern**: Data access abstraction with contracts
- **Service Layer Pattern**: Business logic separation
- **Strategy Pattern**: Multiple news API integrations (NewsAPI, Guardian, NYT)
- **Factory Pattern**: Provider instantiation and model factories for testing

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   API Routes    │────│   Controllers   │────│    Services     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                                        │
                                ┌───────────────────────├──────────────────────┐
                                ▼                                              ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Resources     │◄───│   Repositories  │◄───│  Provider       │◄───│ External APIs   │
│   (Transform)   │    │   (Data Access) │    │  Aggregator     │    │ (News Sources)  │
└─────────────────┘    └─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                                              ▲
                                ▼                                              │
                       ┌─────────────────┐    ┌─────────────────┐              │
                       │    Models       │────│    Database     │              │
                       │   (Eloquent)    │    │  (PostgreSQL)   │              │
                       └─────────────────┘    └─────────────────┘              │
                                                                               │
                       ┌──────────────────────────────────┐                    │
                       │ Background Jobs/Queue Processing │────────────────────┘
                       └──────────────────────────────────┘
```

### Data Flow

```
1. Request → Controller → Request Validation
2. Controller → Service Layer → Business Logic
3. Service → Repository → Database Query
4. Service → Provider Aggregator → External APIs
5. Background Job → Queue → Async Processing
6. Repository → Model → Database Storage
7. Resource → Response Transformation
```

## 📊 Performance Features

### Caching Strategy

The application implements a caching strategy that balances data freshness with API efficiency:

```
Request → Database Query
    ↓
Is data fresh? (within cache time)
    ├─ YES → Return database results immediately
    └─ NO → Should fetch new data
        ├─ Database empty (total === 0)
        │   └─ SYNCHRONOUS: Fetch → Wait → Return fresh data
        └─ Database has data (total > 0)
            ├─ ASYNCHRONOUS: Dispatch background job
            └─ Return existing data immediately
```

#### Cache Configuration

- **Headlines**: 15-minute cache (frequent updates needed)
- **Search**: 60-minute cache (less time-sensitive)
- **Cache Keys**: Include all filter parameters for precise invalidation
- **Background Refresh**: Prevents user-facing API timeouts

### Database Optimization

- **Upsert Operations**: Conflict resolution
- **Indexed Queries**: Optimized search performance
- **Pagination**: Efficient large dataset handling
- **Relationship Loading**: Eager loading to prevent N+1

### Rate Limiting

- **NewsAPI**: 100 requests/day
- **Guardian API**: 1 request/second, 500 requests/day  
- **New York Times**: 5 requests/minute, 500 requests/day
- **Automatic Throttling**: Built-in delays between requests
- **Cache-Based Tracking**: Redis/database-backed request counting
- **Graceful Degradation**: Skip providers that hit limits

### Error Handling

- **Provider Failures**: Graceful degradation
- **Rate Limit Violations**: Automatic provider skipping
- **Logging**: Comprehensive error tracking

## 📁 Project Structure

```
├── app/
│    ├── Exceptions/
│    │   └── HandlerException.php              # Custom exception handling
│    ├── Http/
│    │   ├── Controllers/
│    │   │   ├── Controller.php                # Base controller
│    │   │   └── Api/                          # RESTful API controllers
│    │   │       ├── NewsController.php        # News endpoints
│    │   │       ├── PreferenceController.php  # User preferences
│    │   │       └── SourceController.php      # News sources
│    │   ├── Requests/                         
│    │   │   ├── HeadlinesRequest.php          # Headlines validation
│    │   │   ├── SearchNewsRequest.php         # Search validation
│    │   │   └── PreferenceRequest.php         # Preferences validation
│    │   └── Resources/                        
│    │       ├── ArticleCollection.php         # Article transformation
│    │       ├── ArticleResource.php           # Article transformation
│    │       └── PreferenceResource.php        # Preference transformation
│    ├── Integrations/News/                    # External API integrations
│    │   ├── Contracts/
│    │   │   └── NewsProvider.php              # Provider interface
│    │   ├── DTOs/
│    │   │   └── Article.php                   # Data transfer objects
│    │   ├── Providers/
│    │   │   ├── NewsApiProvider.php           # NewsAPI integration
│    │   │   ├── GuardianProvider.php          # Guardian API integration
│    │   │   └── NytProvider.php               # New York Times API
│    │   ├── Supports/
│    │   │   ├── RateLimitTrait.php            # Rate limiting utilities
│    │   │   └── Taxonomy.php                  # Category mapping
│    │   ├── ProviderAggregator.php            # Provider coordination
│    │   └── ProviderFactory.php               # Provider instantiation
│    ├── Jobs/                                 
│    │   └── FetchNewsArticles.php             # Async news fetching
│    ├── Models/                               
│    │   ├── Article.php                       # News article model
│    │   ├── ArticleSource.php                 # Article-source pivot
│    │   ├── Preference.php                    # User preferences
│    │   ├── Source.php                        # News source model
│    ├── Repositories/                         
│    │   ├── Contracts/                        
│    │   │   ├── ArticleRepository.php         # Article interface
│    │   │   ├── PreferenceRepository.php      # Preference interface
│    │   │   └── SourceRepository.php          # Source interface
│    │   ├── EloquentArticleRepository.php     # Article implementation
│    │   ├── EloquentPreferenceRepository.php  # Preference implementation
│    │   └── EloquentSourceRepository.php      # Source implementation
│    └── Services/                             
│        ├── NewsService.php                   # Core news operations
│        ├── PreferenceService.php             # Preference management
│        └── SourceService.php                 # Source management
├── docker-compose.yml                        # Docker services configuration
├── Dockerfile                                # Application container build
├── run.sh                                    # Application startup script
└── start.sh                                  # Development startup script

```

## 🌐 API Endpoints

### News Endpoints

| Method | Endpoint | Description | Caching |
|--------|----------|-------------|---------|
| `GET` | `/api/news/headlines` | Get top headlines | 15 min |
| `GET` | `/api/news/search` | Search articles | 60 min |
| `GET` | `/api/news/{id}` | Get specific article | N/A |
| `DELETE` | `/api/news/{id}` | Delete article | N/A |

### Preference Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/preferences` | Get current preferences |
| `PUT` | `/api/preferences` | Update preferences |

### Source Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/sources` | Get all available news sources |

### Example Requests

**Get Headlines with Preferences Applied:**
```bash
curl -X GET "http://localhost/api/news/headlines?page=1&pageSize=20"
```

**Search with Override Parameters:**
```bash
curl -X GET "http://localhost/api/news/search?keyword=AI&source=BBC&category=technology"
```

## 🐳 Docker Setup

### Prerequisites

- Docker & Docker Compose
- Git

### Start Everything (Recommended)
```bash
# Easy way - handles everything automatically
./start.sh
```


### Quick Start

1. **Clone the repository:**
```bash
git clone <repository-url>
cd innoscripta-news-aggregator-api
```

2. **Environment setup:**
```bash
cp .env.example .env
# Edit .env with your API keys and database credentials
# NEWS_API_KEY=your_newsapi_key
# GUARDIAN_API_KEY=your_guardian_key
# NYT_API_KEY=your_nyt_key
```

3. **Start with Docker:**
```bash
docker-compose up -d
```

4. **Install dependencies:**
```bash
docker exec -it backend_api composer install
```

5. **Generate application key:**
```bash
docker exec -it backend_api php artisan key:generate
```

6. **Run migrations and seed:**
```bash
docker exec -it backend_api php artisan migrate --seed
```

7. **Run queue:**
```bash
docker exec -it backend_api php artisan queue:work --queue=news
```

### Docker Services

| Service | Port | Description |
|---------|------|-------------|
| `backend_api` | - | Laravel application |
| `nginx` | 80 | Web server |
| `postgres` | 5432 | PostgreSQL database |
| `pgadmin` | 8081 | Database administration |

## ⚙️ Manual Setup

### Requirements

- PHP 8.2+
- Composer
- PostgreSQL 13+
- Redis (for queues)

### Installation Steps

1. **Install dependencies:**
```bash
composer install
```

2. **Environment configuration:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database setup:**
```bash
php artisan migrate --seed
```

4. **Configure API keys in `.env`:**
```env
NEWS_API_KEY=your_newsapi_key
GUARDIAN_API_KEY=your_guardian_key
NYT_API_KEY=your_nyt_key
```

5. **Start queue worker:**
```bash
php artisan queue:work --queue=news
```

6. **Start development server:**
```bash
php artisan serve
```

## 🔧 Configuration

### News Provider Configuration (`config/news.php`)

```php
return [
    'newsapi'  => ['base' => 'https://newsapi.org/v2', 'key' => env('NEWS_API_KEY', '')],
    'guardian' => ['base' => 'https://content.guardianapis.com', 'key' => env('GUARDIAN_API_KEY', '')],
    'nyt'      => ['base' => 'https://api.nytimes.com/svc', 'key' => env('NYT_API_KEY', '')],

    'enabled_providers' => ['newsapi', 'guardian', 'nyt'],

    'freshness' => [
        'headlines_minutes' => 15,  // Cache headlines for 15 minutes
        'search_minutes'    => 60,  // Cache search results for 1 hour
    ],
];
```

### Queue Configuration

```env
QUEUE_CONNECTION=database
# or for Redis:
# QUEUE_CONNECTION=redis
```

## 🔄 Queue Processing

### Background Jobs

The system uses Laravel queues for optimal performance:

```bash
# Start queue worker
php artisan queue:work --queue=news

# Monitor queue status
php artisan queue:monitor news

# Process failed jobs
php artisan queue:retry all
```

### Queue Flow

1. **Immediate Response**: API returns cached data immediately
2. **Background Fetch**: If data is stale, job queued for fresh data
3. **Provider Calls**: Job fetches from multiple providers
4. **Data Merge**: Articles upserted with conflict resolution
5. **Cache Update**: Fresh data available for next request

## 🧪 Testing


### Test Structure

```
tests/
├── Feature/                      
│   ├── AuthenticationTest.php
│   └── NewsControllerTest.php
├── Unit/                      
│   ├── ArticleModelTest.php
│   ├── EloquentArticleRepositoryTest.php
│   ├── NewsApiProviderTest.php
│   └── NewsServiceTest.php
└── TestCase.php              
```

##  API Documentation

### OpenAPI Documentation

The API is fully documented with OpenAPI 3.0 specifications:

```bash
# Generate Swagger documentation
php artisan l5-swagger:generate

# Access documentation at:
http://localhost/api/documentation
```

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Configure cache driver (`redis` recommended)
- [ ] Set up queue workers with supervisor
- [ ] Configure proper logging
- [ ] Set up SSL certificates
- [ ] Configure rate limiting
- [ ] Set up monitoring and alerts

### Environment Variables

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=news_aggregator
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host

# News API Keys
NEWS_API_KEY=your-newsapi-key
GUARDIAN_API_KEY=your-guardian-key
NYT_API_KEY=your-nyt-key
```

**Built with ❤️ using Laravel, following SOLID principles and modern PHP practices.**
