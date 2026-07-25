# 08 — COMPONENT MAP

> Complete component inventory for the entire application.
> Setiap halaman di-dekontruksi menjadi reusable component.

---

## 1. COMPONENT HIERARCHY

```
LAYOUT
├── AppLayout (sidebar + content)
├── AuthLayout (centered card / split)
├── LandingLayout (full width, header + sections)
├── PrintLayout (no chrome)
└── SettingsLayout (sidebar nav + content)

SIDEBAR
├── SidebarContainer (sticky, stashable)
├── SidebarLogo (brand logo)
├── SidebarNav (navlist)
│   ├── SidebarNavItem (single item)
│   ├── SidebarNavGroup (expandable group)
│   └── SidebarNavEventItem (event in list)
├── SidebarEventSwitcher (event selector)
└── SidebarUserMenu (profile + logout)

NAVIGATION
├── TopNav (landing page only)
│   ├── TopNavLogo
│   ├── TopNavLinks
│   └── TopNavCTA
├── Breadcrumb
│   └── BreadcrumbItem
├── BottomTabBar (mobile only)
│   └── BottomTabItem
└── GlobalSearch (future)
    ├── SearchInput
    └── SearchResults

WORKSPACE
├── WorkspaceHeader
│   ├── GreetingText
│   ├── DateDisplay
│   └── ActiveEventCount
├── QuickActions
│   └── QuickActionButton
├── ActiveEventGrid
│   └── EventCard
│       ├── EventCardBadge (event type + status)
│       ├── EventCardSummary (numbers)
│       └── EventCardAction (button)
├── AgendaSection
│   └── AgendaItem
│       ├── AgendaTime
│       ├── AgendaTitle
│       └── AgendaLocation
└── RecentActivityFeed
    └── ActivityItem
        ├── ActivityIcon
        ├── ActivityDescription
        └── ActivityTimestamp

LANDING PAGE
├── HeroSection
│   ├── HeroHeadline
│   ├── HeroSubheadline
│   ├── HeroCTA (buttons)
│   ├── HeroSocialProof
│   └── HeroPreview (mockup image)
├── FeaturesSection
│   └── FeatureCard
│       ├── FeatureIcon
│       ├── FeatureTitle
│       └── FeatureDescription
├── SupportedEventsSection
│   └── EventTypeChip (badge with accent color)
├── StatsSection
│   └── StatCounter (number + label)
├── CTASection
│   ├── CTAHeadline
│   └── CTAButton
└── Footer
    ├── FooterLogo
    ├── FooterLinks
    └── FooterCopyright

AUTH
├── AuthCard (centered container)
├── AuthSplit (brand + form)
├── AuthLogo
├── LoginForm
│   ├── EmailField
│   ├── PasswordField
│   ├── RememberCheckbox
│   ├── SubmitButton
│   ├── ForgotPasswordLink
│   └── RegisterLink
├── RegisterForm
└── ForgotPasswordForm

EVENT HOME
├── EventHeroBanner
│   ├── EventTypeBadge
│   ├── EventStatusBadge
│   ├── EventTitle
│   ├── EventLocation
│   └── EventQuickSummary
├── QuickStatsBar
│   └── StatMiniCard (icon + number + label)
├── ModuleGrid
│   └── ModuleCard
│       ├── ModuleIcon
│       ├── ModuleTitle
│       ├── ModuleDescription
│       └── ModuleAction (link/button)
└── EventActivityFeed
    └── ActivityItem

ANALYTICS
├── AnalyticsHeader
│   ├── AnalyticsTitle
│   └── ExportButton (dropdown)
│       └── ExportOption
├── FilterBar
│   ├── DateRangeFilter
│   └── EventFilter (select)
├── KPIGrid
│   └── KPICard
│       ├── KPILabel
│       ├── KPIValue
│       └── KPITrend (▲/▼ indicator)
├── TrendChart (canvas/SVG)
├── BreakdownTable
│   ├── BreakdownHeader
│   └── BreakdownRow
├── DataTable
│   ├── TableHeader
│   │   └── TableHeaderCell (sortable)
│   ├── TableRow
│   │   └── TableCell
│   └── TablePagination
├── AnalyticsEmptyState
├── ModuleSelector (tab-style)
└── AnalyticsDetail

PEOPLE
├── PeopleHeader
│   ├── PeopleTitle
│   └── AddPersonButton
├── PeopleSearchBar
│   ├── SearchInput
│   └── PeopleFilter
├── PeopleList
│   └── PersonCard
│       ├── PersonAvatar
│       ├── PersonName
│       ├── PersonDetails (desa, kelompok)
│       ├── PersonEventCount
│       └── PersonActions (edit, delete)
├── PersonDetailModal
│   ├── PersonInfo
│   ├── PersonEventHistory
│   └── PersonActions
└── PeopleEmptyState

ADMINISTRATION
├── AdminNav (sidebar within settings)
│   └── AdminNavItem
├── UserManagement
│   ├── UserList
│   │   └── UserRow
│   ├── UserCreateModal
│   ├── UserEditModal
│   └── UserDeleteModal
├── MasterData
│   ├── MasterDataNav (cards: Desa, Kelompok)
│   ├── DesaManagement
│   │   ├── DesaTable
│   │   └── DesaFormModal
│   └── KelompokManagement
│       ├── KelompokTable
│       └── KelompokFormModal
└── EventManagement
    ├── EventTable
    ├── EventCreateModal
    ├── EventEditModal
    └── EventStatusToggle

GENERIC / SHARED
├── Button (flux:button)
│   ├── PrimaryButton
│   ├── DangerButton
│   ├── GhostButton
│   └── IconButton
├── Modal (flux:modal)
│   ├── ConfirmModal
│   ├── FormModal
│   └── DeleteModal
├── Form
│   ├── TextInput (flux:input)
│   ├── SelectField (flux:select)
│   ├── TextArea
│   ├── CheckboxField
│   ├── RadioGroup
│   └── DatePicker
├── Table
│   ├── TableWrapper
│   ├── TableHeader
│   ├── TableRow
│   └── TableCell
├── Badge
│   ├── StatusBadge
│   ├── EventTypeBadge
│   └── RoleBadge
├── Alert
│   ├── SuccessAlert
│   ├── ErrorAlert
│   ├── WarningAlert
│   └── InfoAlert
├── EmptyState
│   ├── TableEmptyState
│   ├── SearchEmptyState
│   └── GenericEmptyState
├── LoadingState
│   ├── ButtonSpinner
│   ├── SkeletonTable
│   ├── SkeletonCard
│   └── FullPageSpinner
├── ErrorState
│   ├── NetworkError
│   ├── ServerError
│   └── PermissionDenied
├── Pagination
├── Card
│   ├── StatCard
│   ├── NavigationCard
│   └── InfoCard
└── Separator (flux:separator)
```

---

## 2. COMPONENT REUSE MAP

### 2.1 Shared Components (Used 3+ Pages)

| Component | Pages | Notes |
|-----------|-------|-------|
| `Table` | People, Analytics, Administration, Event modules | Universal |
| `Modal` | All pages with forms | Universal |
| `Form Fields` | All pages with forms | Universal |
| `Badge` | Workspace, Event Home, Analytics, People | Status + type indicators |
| `Alert` | All pages | Feedback messages |
| `EmptyState` | All data pages | No data handling |
| `LoadingState` | All pages | Loading feedback |
| `Pagination` | People, Analytics, Event modules | Data beyond 1 page |
| `Card` | Workspace, Event Home, Administration | Container pattern |
| `Button` | All pages | Universal |

### 2.2 Page-Specific Components

| Component | Halaman | Alasan |
|-----------|---------|--------|
| `HeroBanner` | Event Home | Event accent + identity |
| `KPIGrid` | Analytics | Hanya analytics yang punya KPI |
| `TrendChart` | Analytics | Hanya analytics yang punya chart |
| `QuickActions` | Workspace | Hanya workspace yang butuh aksi cepat |
| `EventCard` | Workspace | Hanya workspace yang menampilkan grid event |
| `ModuleGrid` | Event Home | Hanya event home yang menampilkan modul |
| `BottomTabBar` | Mobile | Hanya di mobile |
| `HeroSection` | Landing | Hanya landing page |

---

## 3. COMPONENT STATE INVENTORY

Setiap komponen harus mendukung state berikut:

### 3.1 Card / Content Components

| State | Visual | Behavior |
|-------|--------|----------|
| Default | Normal appearance | — |
| Hover | Slight lift + shadow, border highlight | Desktop only |
| Active / Selected | Blue border or background tint | Selected state |
| Disabled | Reduced opacity, no interaction | When unavailable |
| Loading | Skeleton pulse | While content loads |
| Error | Red border + error icon | When data fails |
| Empty | Dashed border + empty icon | No content |

### 3.2 Form Components

| State | Visual | Behavior |
|-------|--------|----------|
| Default | Normal border | — |
| Focus | Blue ring | Keyboard focus |
| Hover | Slightly darker border | Desktop only |
| Disabled | Gray background, reduced opacity | Read-only |
| Error | Red border + error message | Validation failed |
| Success | Green border (optional) | Valid input |
| Loading | Spinner inside or beside | Processing |

### 3.3 Button Components

| State | Visual | Behavior |
|-------|--------|----------|
| Default | Solid (primary) or outline | — |
| Hover | Darker shade | Desktop |
| Active | Scale(0.97) or inset shadow | Click moment |
| Disabled | Opacity-50, no pointer | Not available |
| Loading | Spinner replaces icon | Prevent double click |
| Focus | Blue ring | Keyboard nav |

---

## 4. COMPONENT GROUPING BY PAGE

```
LANDING PAGE
├── TopNav
│   ├── TopNavLogo
│   ├── TopNavLinks
│   └── TopNavCTA
├── HeroSection
│   ├── HeroHeadline
│   ├── HeroSubheadline
│   ├── HeroCTA
│   ├── HeroSocialProof
│   └── HeroPreview
├── FeaturesSection
│   └── FeatureCard × N
├── SupportedEventsSection
│   └── EventTypeChip × N
├── StatsSection
│   └── StatCounter × N
├── CTASection
│   ├── CTAHeadline
│   └── CTAButton
└── Footer
    ├── FooterLogo
    ├── FooterLinks
    └── FooterCopyright

AUTH
├── AuthCard / AuthSplit
├── AuthLogo
├── LoginForm
│   ├── EmailField
│   ├── PasswordField
│   ├── RememberCheckbox
│   ├── SubmitButton
│   ├── ForgotPasswordLink
│   └── RegisterLink
├── RegisterForm
│   ├── NameField
│   ├── EmailField
│   ├── PasswordField
│   ├── ConfirmPasswordField
│   └── SubmitButton
└── ForgotPasswordForm

WORKSPACE
├── WorkspaceHeader
│   ├── GreetingText
│   ├── DateDisplay
│   └── ActiveEventCount
├── QuickActions
│   └── QuickActionButton × 3-4
├── ActiveEventGrid
│   └── EventCard × N
│       ├── EventTypeBadge
│       ├── EventStatusBadge
│       ├── EventCardSummary
│       └── EventCardAction
├── AgendaSection
│   └── AgendaItem × N
└── RecentActivityFeed
    └── ActivityItem × N

EVENT HOME
├── EventHeroBanner
│   ├── EventTypeBadge
│   ├── EventStatusBadge
│   ├── EventTitle
│   ├── EventLocation
│   └── EventQuickSummary
├── QuickStatsBar
│   └── StatMiniCard × 3-4
├── ModuleGrid
│   └── ModuleCard × N
└── EventActivityFeed

ANALYTICS
├── AnalyticsHeader
│   ├── AnalyticsTitle
│   └── ExportButton
├── FilterBar
│   ├── DateRangeFilter
│   └── EventFilter
├── KPIGrid
│   └── KPICard × 4
├── TrendChart
├── BreakdownTable
├── DataTable
└── AnalyticsEmptyState

PEOPLE
├── PeopleHeader
│   ├── PeopleTitle
│   └── AddPersonButton
├── PeopleSearchBar
├── PeopleList
│   └── PersonCard × N
└── PeopleEmptyState

ADMINISTRATION
├── AdminNav
├── UserManagement
├── MasterData
│   ├── DesaManagement
│   └── KelompokManagement
└── EventManagement
```

---

## 5. COMPONENT NAMING CONVENTION

| Type | Pattern | Contoh |
|------|---------|--------|
| Page-level | `{Page}{Element}` | `WorkspaceHeader` |
| Reusable | `{Element}` | `Table`, `Modal`, `Badge` |
| Generic with variant | `{Element}{Variant}` | `ButtonPrimary`, `AlertError` |
| State variant | `{Element}{State}` | `EmptyState`, `LoadingState` |

---

## 6. RULES

| Rule | Detail |
|------|--------|
| Setiap komponen harus memiliki default, loading, error, dan empty state | Kecuali komponen dekoratif |
| Shared components harus di Blade `components/` | Bukan di page-specific |
| Page-specific components boleh di Livewire view | Tapi usahakan ekstrak jika reuse 3+ |
| Component naming harus konsisten | PascalCase, English code |
| Jangan buat komponen untuk satu kali penggunaan | Kecuali sangat kompleks |
| Dokumentasikan setiap komponen di COMPONENT_LIBRARY.md | Reference untuk AI coding |
