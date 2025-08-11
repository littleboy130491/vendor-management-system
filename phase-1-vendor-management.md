# Phase 1: Vendor Management Module
**Duration:** 1.5-2 weeks  
**Team:** 2-3 developers  
**Prerequisites:** Phase 0 completed successfully

## Overview
This phase implements the core vendor management functionality, including vendor registration, document management, approval workflows, and basic vendor operations through Filament admin interface.

## Objectives
- Implement complete vendor CRUD operations
- Create vendor onboarding and approval workflow
- Implement document management system
- Build vendor categorization system
- Create vendor profile and rating system
- Implement basic reporting for vendors

## Tasks Breakdown

### 1. Database Schema Implementation
**Estimated Time:** 1 day

#### Tasks:
1. **Create Vendor-Related Models and Migrations**
   ```bash
   php artisan make:model Vendor -m
   php artisan make:model VendorCategory -m
   php artisan make:model VendorDocument -m
   php artisan make:model VendorReview -m
   php artisan make:model VendorWarning -m
   ```

2. **Define Migration Structures**
   ```php
   // Migration for vendors table
   Schema::create('vendors', function (Blueprint $table) {
       $table->id();
       $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
       $table->string('company_name');
       $table->string('slug')->unique();
       $table->foreignId('category_id')->constrained('vendor_categories');
       $table->string('contact_name');
       $table->string('contact_email');
       $table->string('contact_phone')->nullable();
       $table->text('address')->nullable();
       $table->string('tax_id')->unique()->nullable();
       $table->enum('status', ['pending', 'active', 'suspended', 'blacklisted'])->default('pending');
       $table->decimal('rating_average', 3, 2)->default(0.00);
       $table->json('metadata')->nullable();
       $table->timestamps();
       
       $table->index(['status', 'category_id']);
       $table->index('rating_average');
   });
   
   // Migration for vendor_categories table
   Schema::create('vendor_categories', function (Blueprint $table) {
       $table->id();
       $table->string('name');
       $table->text('description')->nullable();
       $table->timestamps();
   });
   
   // Migration for vendor_documents table  
   Schema::create('vendor_documents', function (Blueprint $table) {
       $table->id();
       $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
       $table->string('collection_name');
       $table->string('file_name');
       $table->bigInteger('file_size');
       $table->string('mime_type');
       $table->json('custom_properties')->nullable();
       $table->timestamp('expires_at')->nullable();
       $table->timestamps();
   });
   
   // Migration for vendor_reviews table
   Schema::create('vendor_reviews', function (Blueprint $table) {
       $table->id();
       $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
       $table->foreignId('reviewer_id')->constrained('users');
       $table->tinyInteger('rating_quality')->unsigned();
       $table->tinyInteger('rating_timeliness')->unsigned();
       $table->tinyInteger('rating_communication')->unsigned();
       $table->text('comments')->nullable();
       $table->timestamps();
       
       $table->check('rating_quality >= 1 AND rating_quality <= 5');
       $table->check('rating_timeliness >= 1 AND rating_timeliness <= 5');
       $table->check('rating_communication >= 1 AND rating_communication <= 5');
   });
   
   // Migration for vendor_warnings table
   Schema::create('vendor_warnings', function (Blueprint $table) {
       $table->id();
       $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
       $table->foreignId('issued_by')->constrained('users');
       $table->string('type');
       $table->text('details');
       $table->timestamp('resolved_at')->nullable();
       $table->timestamps();
   });
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

#### Expected Output:
- All vendor-related database tables created
- Proper foreign key relationships established
- Database indexes created for performance
- Migration files properly documented

### 2. Eloquent Models and Relationships
**Estimated Time:** 1 day

#### Tasks:
1. **Define Vendor Model**
   ```php
   class Vendor extends Model
   {
       use HasFactory, HasMedia, HasSlug;
       
       protected $fillable = [
           'user_id', 'company_name', 'slug', 'category_id',
           'contact_name', 'contact_email', 'contact_phone',
           'address', 'tax_id', 'status', 'rating_average', 'metadata'
       ];
       
       protected $casts = [
           'metadata' => 'array',
           'rating_average' => 'decimal:2'
       ];
       
       // Relationships
       public function user() { return $this->belongsTo(User::class); }
       public function category() { return $this->belongsTo(VendorCategory::class); }
       public function reviews() { return $this->hasMany(VendorReview::class); }
       public function warnings() { return $this->hasMany(VendorWarning::class); }
       
       // Accessors & Mutators
       public function getSlugOptions(): SlugOptions
       {
           return SlugOptions::create()
               ->generateSlugsFrom('company_name')
               ->saveSlugsTo('slug');
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('status', 'active');
       }
       
       public function scopePending($query) {
           return $query->where('status', 'pending');
       }
       
       // Methods
       public function updateRatingAverage() {
           $average = $this->reviews()
               ->selectRaw('AVG((rating_quality + rating_timeliness + rating_communication) / 3) as avg_rating')
               ->first()
               ->avg_rating;
               
           $this->update(['rating_average' => round($average, 2)]);
       }
   }
   ```

2. **Define Other Model Classes**
   - VendorCategory with name and description
   - VendorDocument with media library integration
   - VendorReview with rating calculations
   - VendorWarning with status tracking

3. **Update User Model**
   ```php
   class User extends Authenticatable
   {
       use HasRoles;
       
       public function vendor() {
           return $this->hasOne(Vendor::class);
       }
       
       public function isVendor() {
           return $this->hasRole('vendor');
       }
   }
   ```

#### Expected Output:
- All model classes created with proper relationships
- Media library integration functional
- Rating calculation system implemented
- Proper model scopes and methods defined

### 3. Filament Resources Implementation
**Estimated Time:** 2 days

#### Tasks:
1. **Create Vendor Resource**
   ```bash
   php artisan make:filament-resource Vendor --generate
   ```

2. **Customize Vendor Resource**
   ```php
   class VendorResource extends Resource
   {
       protected static ?string $model = Vendor::class;
       protected static ?string $navigationIcon = 'heroicon-o-building-office';
       protected static ?string $navigationGroup = 'Vendor Management';
       
       public static function form(Form $form): Form
       {
           return $form->schema([
               Section::make('Company Information')
                   ->schema([
                       TextInput::make('company_name')
                           ->required()
                           ->maxLength(255),
                       Select::make('category_id')
                           ->relationship('category', 'name')
                           ->required()
                           ->createOptionForm([
                               TextInput::make('name')->required(),
                               Textarea::make('description')
                           ]),
                       TextInput::make('tax_id')
                           ->label('Tax ID')
                           ->maxLength(255),
                       Textarea::make('address')
                           ->rows(3),
                   ])->columns(2),
                   
               Section::make('Contact Information')
                   ->schema([
                       TextInput::make('contact_name')
                           ->required()
                           ->maxLength(255),
                       TextInput::make('contact_email')
                           ->email()
                           ->required(),
                       TextInput::make('contact_phone')
                           ->tel(),
                   ])->columns(2),
                   
               Section::make('Status & Settings')
                   ->schema([
                       Select::make('status')
                           ->options([
                               'pending' => 'Pending Approval',
                               'active' => 'Active',
                               'suspended' => 'Suspended',
                               'blacklisted' => 'Blacklisted'
                           ])
                           ->required(),
                       Select::make('user_id')
                           ->relationship('user', 'email')
                           ->searchable()
                           ->nullable()
                           ->helperText('Link to existing user account'),
                       KeyValue::make('metadata')
                           ->keyLabel('Field')
                           ->valueLabel('Value'),
                   ])->columns(2),
                   
               Section::make('Documents')
                   ->schema([
                       SpatieMediaLibraryFileUpload::make('documents')
                           ->collection('vendor_documents')
                           ->multiple()
                           ->acceptedFileTypes(['application/pdf', 'image/*'])
                           ->helperText('Upload vendor documents (certificates, licenses, etc.)')
                   ])
           ]);
       }
       
       public static function table(Table $table): Table
       {
           return $table
               ->columns([
                   TextColumn::make('company_name')
                       ->searchable()
                       ->sortable(),
                   TextColumn::make('category.name')
                       ->badge()
                       ->color('primary'),
                   TextColumn::make('contact_email')
                       ->searchable(),
                   TextColumn::make('status')
                       ->badge()
                       ->color(fn (string $state): string => match ($state) {
                           'active' => 'success',
                           'pending' => 'warning',
                           'suspended' => 'danger',
                           'blacklisted' => 'danger',
                       }),
                   TextColumn::make('rating_average')
                       ->label('Rating')
                       ->formatStateUsing(fn ($state) => number_format($state, 1) . '/5.0')
                       ->sortable(),
                   TextColumn::make('created_at')
                       ->dateTime()
                       ->sortable()
                       ->toggleable(isToggledHiddenByDefault: true),
               ])
               ->filters([
                   SelectFilter::make('status')
                       ->options([
                           'pending' => 'Pending',
                           'active' => 'Active',
                           'suspended' => 'Suspended',
                           'blacklisted' => 'Blacklisted'
                       ]),
                   SelectFilter::make('category_id')
                       ->relationship('category', 'name')
                       ->label('Category')
               ])
               ->actions([
                   Tables\Actions\ViewAction::make(),
                   Tables\Actions\EditAction::make(),
                   Tables\Actions\Action::make('approve')
                       ->icon('heroicon-o-check-circle')
                       ->color('success')
                       ->visible(fn ($record) => $record->status === 'pending')
                       ->action(function ($record) {
                           $record->update(['status' => 'active']);
                           Notification::make()
                               ->title('Vendor approved successfully')
                               ->success()
                               ->send();
                       }),
                   Tables\Actions\Action::make('suspend')
                       ->icon('heroicon-o-x-circle')
                       ->color('warning')
                       ->visible(fn ($record) => $record->status === 'active')
                       ->requiresConfirmation()
                       ->action(function ($record) {
                           $record->update(['status' => 'suspended']);
                       })
               ])
               ->bulkActions([
                   Tables\Actions\BulkActionGroup::make([
                       Tables\Actions\DeleteBulkAction::make(),
                       Tables\Actions\BulkAction::make('approve')
                           ->label('Approve Selected')
                           ->icon('heroicon-o-check-circle')
                           ->color('success')
                           ->action(function (Collection $records) {
                               $records->each(fn ($record) => $record->update(['status' => 'active']));
                           })
                   ]),
               ]);
       }
   }
   ```

3. **Create Supporting Resources**
   ```bash
   php artisan make:filament-resource VendorCategory --simple
   php artisan make:filament-resource VendorReview
   php artisan make:filament-resource VendorWarning
   ```

#### Expected Output:
- Fully functional Vendor resource with CRUD operations
- Approval workflow integrated into table actions
- Document upload functionality working
- Category management system functional
- Review and warning systems accessible

### 4. Business Logic and Services
**Estimated Time:** 1.5 days

#### Tasks:
1. **Create VendorOnboardingService**
   ```php
   class VendorOnboardingService
   {
       public function createVendor(array $data): Vendor
       {
           DB::transaction(function () use ($data) {
               $vendor = Vendor::create($data);
               
               // Send notification to admins
               $this->notifyAdminsOfNewVendor($vendor);
               
               // Create activity log
               activity('vendor_created')
                   ->performedOn($vendor)
                   ->log('New vendor registration: ' . $vendor->company_name);
                   
               return $vendor;
           });
       }
       
       public function approveVendor(Vendor $vendor, User $approver): bool
       {
           return DB::transaction(function () use ($vendor, $approver) {
               $vendor->update(['status' => 'active']);
               
               // Create user account if not exists
               if (!$vendor->user_id) {
                   $user = $this->createVendorUser($vendor);
                   $vendor->update(['user_id' => $user->id]);
               }
               
               // Send approval notification
               $this->notifyVendorApproval($vendor);
               
               // Log activity
               activity('vendor_approved')
                   ->performedOn($vendor)
                   ->causedBy($approver)
                   ->log('Vendor approved: ' . $vendor->company_name);
                   
               return true;
           });
       }
       
       private function createVendorUser(Vendor $vendor): User
       {
           $user = User::create([
               'name' => $vendor->contact_name,
               'email' => $vendor->contact_email,
               'password' => Hash::make(Str::random(12))
           ]);
           
           $user->assignRole('vendor');
           
           return $user;
       }
   }
   ```

2. **Create VendorRatingService**
   ```php
   class VendorRatingService
   {
       public function addReview(Vendor $vendor, User $reviewer, array $ratings): VendorReview
       {
           $review = VendorReview::create([
               'vendor_id' => $vendor->id,
               'reviewer_id' => $reviewer->id,
               'rating_quality' => $ratings['quality'],
               'rating_timeliness' => $ratings['timeliness'],
               'rating_communication' => $ratings['communication'],
               'comments' => $ratings['comments'] ?? null
           ]);
           
           // Update vendor's average rating
           $vendor->updateRatingAverage();
           
           return $review;
       }
       
       public function getVendorRankings(VendorCategory $category = null): Collection
       {
           return Vendor::query()
               ->when($category, fn ($q) => $q->where('category_id', $category->id))
               ->where('status', 'active')
               ->orderByDesc('rating_average')
               ->with(['category', 'reviews'])
               ->get();
       }
   }
   ```

3. **Create Event Classes**
   ```bash
   php artisan make:event VendorApproved
   php artisan make:event VendorSuspended
   php artisan make:listener SendVendorApprovalNotification
   ```

#### Expected Output:
- VendorOnboardingService handling vendor creation and approval
- VendorRatingService managing ratings and rankings  
- Event-driven architecture for vendor status changes
- Proper transaction handling and error management
- Activity logging for audit trails

### 5. Policies and Authorization
**Estimated Time:** 1 day

#### Tasks:
1. **Create Vendor Policy**
   ```bash
   php artisan make:policy VendorPolicy --model=Vendor
   ```

2. **Define Policy Methods**
   ```php
   class VendorPolicy
   {
       public function viewAny(User $user): bool
       {
           return $user->can('view_vendors');
       }
       
       public function view(User $user, Vendor $vendor): bool
       {
           if ($user->can('view_vendors')) return true;
           
           // Vendors can only view their own profile
           return $user->id === $vendor->user_id;
       }
       
       public function create(User $user): bool
       {
           return $user->can('manage_vendors');
       }
       
       public function update(User $user, Vendor $vendor): bool
       {
           if ($user->can('manage_vendors')) return true;
           
           // Vendors can update their own basic info
           return $user->id === $vendor->user_id && $vendor->status !== 'blacklisted';
       }
       
       public function approve(User $user, Vendor $vendor): bool
       {
           return $user->can('approve_vendors') && $vendor->status === 'pending';
       }
       
       public function suspend(User $user, Vendor $vendor): bool
       {
           return $user->can('manage_vendors') && $vendor->status === 'active';
       }
   }
   ```

3. **Register Policy in AuthServiceProvider**
   ```php
   protected $policies = [
       Vendor::class => VendorPolicy::class,
   ];
   ```

#### Expected Output:
- Comprehensive vendor policy covering all operations
- Role-based access control properly implemented
- Vendor self-service capabilities defined
- Admin approval workflows secured

### 6. Seeders and Test Data
**Estimated Time:** 0.5 days

#### Tasks:
1. **Create VendorCategorySeeder**
   ```php
   class VendorCategorySeeder extends Seeder
   {
       public function run()
       {
           $categories = [
               ['name' => 'IT Services', 'description' => 'Software development, IT consulting, system integration'],
               ['name' => 'Office Supplies', 'description' => 'Stationery, furniture, office equipment'],
               ['name' => 'Professional Services', 'description' => 'Legal, accounting, consulting services'],
               ['name' => 'Maintenance', 'description' => 'Building maintenance, cleaning, repairs'],
               ['name' => 'Marketing', 'description' => 'Advertising, digital marketing, branding']
           ];
           
           foreach ($categories as $category) {
               VendorCategory::create($category);
           }
       }
   }
   ```

2. **Create VendorSeeder with Factory**
   ```bash
   php artisan make:factory VendorFactory
   ```
   
   ```php
   class VendorFactory extends Factory
   {
       protected $model = Vendor::class;
       
       public function definition()
       {
           return [
               'company_name' => $this->faker->company(),
               'category_id' => VendorCategory::factory(),
               'contact_name' => $this->faker->name(),
               'contact_email' => $this->faker->companyEmail(),
               'contact_phone' => $this->faker->phoneNumber(),
               'address' => $this->faker->address(),
               'tax_id' => $this->faker->unique()->numerify('TAX###########'),
               'status' => $this->faker->randomElement(['pending', 'active', 'suspended']),
               'rating_average' => $this->faker->randomFloat(2, 1, 5)
           ];
       }
   }
   ```

#### Expected Output:
- Vendor categories seeded with realistic data
- Vendor factory for testing and demonstration
- Sample vendors with various statuses and ratings
- Consistent test data for development

### 7. Testing Implementation
**Estimated Time:** 1 day

#### Tasks:
1. **Feature Tests for Vendor Operations**
   ```php
   // tests/Feature/VendorManagementTest.php
   class VendorManagementTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_admin_can_view_vendors_list()
       {
           $admin = User::factory()->create();
           $admin->assignRole('super_admin');
           
           Vendor::factory()->count(3)->create();
           
           $this->actingAs($admin)
                ->get('/admin/vendors')
                ->assertOk()
                ->assertSee('Vendors');
       }
       
       public function test_admin_can_approve_pending_vendor()
       {
           $admin = User::factory()->create();
           $admin->assignRole('super_admin');
           $vendor = Vendor::factory()->create(['status' => 'pending']);
           
           $service = new VendorOnboardingService();
           
           $result = $service->approveVendor($vendor, $admin);
           
           $this->assertTrue($result);
           $this->assertEquals('active', $vendor->fresh()->status);
       }
       
       public function test_vendor_can_only_view_own_profile()
       {
           $vendorUser = User::factory()->create();
           $vendorUser->assignRole('vendor');
           $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);
           $otherVendor = Vendor::factory()->create();
           
           $this->actingAs($vendorUser)
                ->get('/admin/vendors/' . $vendor->id)
                ->assertOk();
                
           $this->actingAs($vendorUser)
                ->get('/admin/vendors/' . $otherVendor->id)
                ->assertForbidden();
       }
   }
   ```

2. **Unit Tests for Services**
   ```php
   // tests/Unit/VendorRatingServiceTest.php
   class VendorRatingServiceTest extends TestCase
   {
       public function test_rating_calculation_updates_vendor_average()
       {
           $vendor = Vendor::factory()->create(['rating_average' => 0]);
           $reviewer = User::factory()->create();
           
           $service = new VendorRatingService();
           
           $service->addReview($vendor, $reviewer, [
               'quality' => 4,
               'timeliness' => 5,
               'communication' => 3,
               'comments' => 'Good service overall'
           ]);
           
           $expectedAverage = (4 + 5 + 3) / 3;
           $this->assertEquals($expectedAverage, $vendor->fresh()->rating_average);
       }
   }
   ```

#### Expected Output:
- Comprehensive feature tests covering vendor workflows
- Unit tests for business logic services
- Authorization tests for policy enforcement
- All tests passing with proper coverage

## Quality Assurance Checklist

### Functional Tests
- [ ] Vendor CRUD operations work correctly in Filament admin
- [ ] Vendor approval workflow functions properly
- [ ] Document upload and management system operational
- [ ] Rating system calculates averages correctly
- [ ] Vendor categorization system works
- [ ] Search and filtering functionality operational
- [ ] Email notifications sent on vendor approval/rejection
- [ ] Activity logging captures all vendor actions

### Security Tests
- [ ] Role-based access control properly enforced
- [ ] Vendors cannot access other vendors' data
- [ ] File upload security measures in place
- [ ] SQL injection prevention verified
- [ ] XSS protection implemented

### Performance Tests
- [ ] Vendor listing page loads efficiently with large datasets
- [ ] Search functionality performs well
- [ ] Database queries optimized with proper indexes
- [ ] File upload handles large files appropriately

## Expected Deliverables

1. **Complete Vendor Management System**
   - Full CRUD operations via Filament admin interface
   - Vendor approval workflow with notifications
   - Document management with file upload capabilities

2. **Database Structure**
   - All vendor-related tables properly migrated
   - Relationships and constraints correctly implemented
   - Indexes optimized for performance

3. **Business Logic Implementation**
   - VendorOnboardingService for vendor lifecycle management
   - VendorRatingService for rating calculations
   - Event-driven architecture for vendor status changes

4. **Authorization System**
   - Comprehensive policy implementation
   - Role-based access control
   - Vendor self-service capabilities

5. **Testing Suite**
   - Feature tests covering all major workflows
   - Unit tests for business logic
   - Authorization tests for security

## Success Criteria

✅ **Phase 1 Complete When:**
1. Admins can successfully manage vendors through Filament interface
2. Vendor approval workflow is functional with notifications
3. Document upload and management system is operational
4. Rating system accurately calculates and displays vendor ratings
5. Authorization policies properly restrict access
6. All tests are passing (minimum 80% coverage)
7. Sample data is seeded for demonstration purposes
8. System is ready for RFQ module development (Phase 2)

## Next Phase Preparation

**Preparation for Phase 2 (RFQ Module):**
- Vendor model should support RFQ invitations
- Notification system should be ready for RFQ workflows
- User authentication should support vendor user accounts
- Document management should support RFQ response documents

**Dependencies for Next Phase:**
- Active vendors available in the system
- User roles properly configured
- Email notification system operational
- File upload and storage system functional

---

**Dependencies:** Phase 0 (Foundation setup)  
**Risks:** Complex approval workflows, document upload security, performance with large vendor datasets  
**Mitigation:** Incremental testing, security review, performance monitoring, staged rollout
