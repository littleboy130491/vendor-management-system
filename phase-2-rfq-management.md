# Phase 2: RFQ (Request for Quotation) Management Module
**Duration:** 1.5-2 weeks  
**Team:** 2-3 developers  
**Prerequisites:** Phase 0 (Foundation) and Phase 1 (Vendor Management) completed successfully

## Overview
This phase implements the RFQ (Request for Quotation) system, allowing procurement officers to create RFQs, invite vendors, manage vendor responses, and evaluate bids for contract awards.

## Objectives
- Implement complete RFQ lifecycle management
- Create vendor invitation and response system
- Build bid comparison and evaluation tools
- Implement RFQ status workflow
- Create notification system for RFQ activities
- Implement vendor bid submission interface

## Tasks Breakdown

### 1. Database Schema Implementation
**Estimated Time:** 1 day

#### Tasks:
1. **Create RFQ-Related Models and Migrations**
   ```bash
   php artisan make:model RFQ -m
   php artisan make:model RFQResponse -m
   php artisan make:pivot RFQVendor
   ```

2. **Define Migration Structures**
   ```php
   // Migration for rfqs table
   Schema::create('rfqs', function (Blueprint $table) {
       $table->id();
       $table->string('title');
       $table->string('slug')->unique();
       $table->text('description');
       $table->foreignId('created_by')->constrained('users');
       $table->timestamp('starts_at');
       $table->timestamp('ends_at');
       $table->enum('status', ['draft', 'published', 'closed', 'awarded'])->default('draft');
       $table->json('scope')->nullable(); // RFQ requirements, specifications
       $table->decimal('budget_min', 15, 2)->nullable();
       $table->decimal('budget_max', 15, 2)->nullable();
       $table->json('evaluation_criteria')->nullable(); // Scoring criteria
       $table->timestamps();
       
       $table->index(['status', 'starts_at', 'ends_at']);
       $table->index('created_by');
   });
   
   // Migration for rfq_vendors pivot table
   Schema::create('rfq_vendors', function (Blueprint $table) {
       $table->id();
       $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
       $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
       $table->timestamp('invited_at');
       $table->enum('status', ['invited', 'viewed', 'responded', 'declined'])->default('invited');
       $table->timestamp('viewed_at')->nullable();
       $table->timestamp('responded_at')->nullable();
       $table->timestamps();
       
       $table->unique(['rfq_id', 'vendor_id']);
       $table->index(['rfq_id', 'status']);
   });
   
   // Migration for rfq_responses table
   Schema::create('rfq_responses', function (Blueprint $table) {
       $table->id();
       $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
       $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
       $table->decimal('quoted_amount', 15, 2);
       $table->integer('delivery_time_days');
       $table->text('technical_proposal')->nullable();
       $table->text('commercial_proposal')->nullable();
       $table->json('line_items')->nullable(); // Detailed pricing breakdown
       $table->json('attachments')->nullable(); // File references
       $table->enum('status', ['submitted', 'under_review', 'shortlisted', 'rejected', 'awarded'])->default('submitted');
       $table->timestamp('submitted_at');
       $table->decimal('evaluation_score', 5, 2)->nullable();
       $table->json('evaluation_notes')->nullable();
       $table->timestamps();
       
       $table->unique(['rfq_id', 'vendor_id']);
       $table->index(['rfq_id', 'status']);
       $table->index('quoted_amount');
   });
   
   // Migration for rfq_evaluations table
   Schema::create('rfq_evaluations', function (Blueprint $table) {
       $table->id();
       $table->foreignId('rfq_response_id')->constrained()->onDelete('cascade');
       $table->foreignId('evaluator_id')->constrained('users');
       $table->json('criteria_scores'); // Individual criterion scores
       $table->decimal('total_score', 5, 2);
       $table->text('comments')->nullable();
       $table->boolean('recommend_award')->default(false);
       $table->timestamps();
       
       $table->unique(['rfq_response_id', 'evaluator_id']);
   });
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

#### Expected Output:
- All RFQ-related database tables created
- Proper foreign key relationships established
- Indexes created for efficient querying
- Pivot tables for many-to-many relationships

### 2. Eloquent Models and Relationships
**Estimated Time:** 1 day

#### Tasks:
1. **Define RFQ Model**
   ```php
   class RFQ extends Model
   {
       use HasFactory, HasMedia, HasSlug;
       
       protected $fillable = [
           'title', 'slug', 'description', 'created_by', 'starts_at', 
           'ends_at', 'status', 'scope', 'budget_min', 'budget_max',
           'evaluation_criteria'
       ];
       
       protected $casts = [
           'starts_at' => 'datetime',
           'ends_at' => 'datetime',
           'scope' => 'array',
           'evaluation_criteria' => 'array',
           'budget_min' => 'decimal:2',
           'budget_max' => 'decimal:2'
       ];
       
       // Relationships
       public function creator() { 
           return $this->belongsTo(User::class, 'created_by'); 
       }
       
       public function vendors() { 
           return $this->belongsToMany(Vendor::class, 'rfq_vendors')
                       ->withPivot(['invited_at', 'status', 'viewed_at', 'responded_at'])
                       ->withTimestamps();
       }
       
       public function responses() { 
           return $this->hasMany(RFQResponse::class); 
       }
       
       public function invitedVendors() {
           return $this->belongsToMany(Vendor::class, 'rfq_vendors')
                       ->wherePivot('status', 'invited');
       }
       
       public function respondedVendors() {
           return $this->belongsToMany(Vendor::class, 'rfq_vendors')
                       ->wherePivot('status', 'responded');
       }
       
       // Accessors
       public function getSlugOptions(): SlugOptions
       {
           return SlugOptions::create()
               ->generateSlugsFrom('title')
               ->saveSlugsTo('slug');
       }
       
       public function getBudgetRangeAttribute(): string
       {
           if ($this->budget_min && $this->budget_max) {
               return number_format($this->budget_min, 2) . ' - ' . number_format($this->budget_max, 2);
           }
           return 'Not specified';
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('status', 'published')
                       ->where('starts_at', '<=', now())
                       ->where('ends_at', '>=', now());
       }
       
       public function scopeForVendor($query, Vendor $vendor) {
           return $query->whereHas('vendors', function ($q) use ($vendor) {
               $q->where('vendor_id', $vendor->id);
           });
       }
       
       // Methods
       public function isActive(): bool
       {
           return $this->status === 'published' 
               && $this->starts_at <= now() 
               && $this->ends_at >= now();
       }
       
       public function hasExpired(): bool
       {
           return $this->ends_at < now();
       }
       
       public function canReceiveResponses(): bool
       {
           return $this->isActive() && !$this->hasExpired();
       }
       
       public function inviteVendors(array $vendorIds): void
       {
           foreach ($vendorIds as $vendorId) {
               $this->vendors()->attach($vendorId, [
                   'invited_at' => now(),
                   'status' => 'invited'
               ]);
           }
       }
   }
   ```

2. **Define RFQResponse Model**
   ```php
   class RFQResponse extends Model
   {
       use HasFactory, HasMedia;
       
       protected $fillable = [
           'rfq_id', 'vendor_id', 'quoted_amount', 'delivery_time_days',
           'technical_proposal', 'commercial_proposal', 'line_items',
           'attachments', 'status', 'submitted_at', 'evaluation_score',
           'evaluation_notes'
       ];
       
       protected $casts = [
           'quoted_amount' => 'decimal:2',
           'line_items' => 'array',
           'attachments' => 'array',
           'submitted_at' => 'datetime',
           'evaluation_score' => 'decimal:2',
           'evaluation_notes' => 'array'
       ];
       
       // Relationships
       public function rfq() { 
           return $this->belongsTo(RFQ::class); 
       }
       
       public function vendor() { 
           return $this->belongsTo(Vendor::class); 
       }
       
       public function evaluations() {
           return $this->hasMany(RFQEvaluation::class);
       }
       
       // Accessors
       public function getTotalLineItemsAttribute(): decimal
       {
           if (!$this->line_items) return 0;
           
           return collect($this->line_items)->sum(function ($item) {
               return ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
           });
       }
       
       public function getAverageEvaluationScoreAttribute(): ?float
       {
           return $this->evaluations()->avg('total_score');
       }
       
       // Methods
       public function calculateScore(array $criteria): float
       {
           $totalScore = 0;
           $totalWeight = 0;
           
           foreach ($criteria as $criterion => $weight) {
               $score = $this->evaluation_notes[$criterion] ?? 0;
               $totalScore += $score * $weight;
               $totalWeight += $weight;
           }
           
           return $totalWeight > 0 ? $totalScore / $totalWeight : 0;
       }
       
       public function isWinner(): bool
       {
           return $this->status === 'awarded';
       }
   }
   ```

3. **Create RFQEvaluation Model**
   ```php
   class RFQEvaluation extends Model
   {
       protected $fillable = [
           'rfq_response_id', 'evaluator_id', 'criteria_scores',
           'total_score', 'comments', 'recommend_award'
       ];
       
       protected $casts = [
           'criteria_scores' => 'array',
           'total_score' => 'decimal:2',
           'recommend_award' => 'boolean'
       ];
       
       public function response() { 
           return $this->belongsTo(RFQResponse::class, 'rfq_response_id'); 
       }
       
       public function evaluator() { 
           return $this->belongsTo(User::class, 'evaluator_id'); 
       }
   }
   ```

#### Expected Output:
- All RFQ models with proper relationships
- Business logic methods for RFQ lifecycle
- Proper casting and accessors for data handling
- Scopes for common queries

### 3. Filament Resources Implementation
**Estimated Time:** 2.5 days

#### Tasks:
1. **Create RFQ Resource**
   ```bash
   php artisan make:filament-resource RFQ --generate
   ```

2. **Customize RFQ Resource**
   ```php
   class RFQResource extends Resource
   {
       protected static ?string $model = RFQ::class;
       protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
       protected static ?string $navigationGroup = 'Procurement';
       
       public static function form(Form $form): Form
       {
           return $form->schema([
               Section::make('Basic Information')
                   ->schema([
                       TextInput::make('title')
                           ->required()
                           ->maxLength(255)
                           ->live(onBlur: true)
                           ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                       
                       TextInput::make('slug')
                           ->required()
                           ->maxLength(255)
                           ->unique(ignoreRecord: true),
                       
                       Textarea::make('description')
                           ->required()
                           ->rows(4),
                           
                       Select::make('status')
                           ->options([
                               'draft' => 'Draft',
                               'published' => 'Published',
                               'closed' => 'Closed',
                               'awarded' => 'Awarded'
                           ])
                           ->required(),
                   ])->columns(2),
                   
               Section::make('Timeline')
                   ->schema([
                       DateTimePicker::make('starts_at')
                           ->required()
                           ->after('today'),
                           
                       DateTimePicker::make('ends_at')
                           ->required()
                           ->after('starts_at'),
                   ])->columns(2),
                   
               Section::make('Budget Information')
                   ->schema([
                       TextInput::make('budget_min')
                           ->label('Minimum Budget')
                           ->numeric()
                           ->prefix('$'),
                           
                       TextInput::make('budget_max')
                           ->label('Maximum Budget')
                           ->numeric()
                           ->prefix('$')
                           ->gte('budget_min'),
                   ])->columns(2),
                   
               Section::make('Requirements & Scope')
                   ->schema([
                       KeyValue::make('scope')
                           ->keyLabel('Requirement')
                           ->valueLabel('Description'),
                           
                       KeyValue::make('evaluation_criteria')
                           ->keyLabel('Criterion')
                           ->valueLabel('Weight (%)')
                           ->helperText('Define evaluation criteria and their weights (should total 100%)'),
                   ]),
                   
               Section::make('Vendor Invitations')
                   ->schema([
                       Select::make('invited_vendors')
                           ->multiple()
                           ->options(
                               Vendor::active()
                                   ->pluck('company_name', 'id')
                                   ->toArray()
                           )
                           ->searchable()
                           ->helperText('Select vendors to invite for this RFQ')
                   ])
                   ->hiddenOn('create'),
                   
               Section::make('Documents')
                   ->schema([
                       SpatieMediaLibraryFileUpload::make('documents')
                           ->collection('rfq_documents')
                           ->multiple()
                           ->acceptedFileTypes(['application/pdf', 'application/msword', 'image/*'])
                           ->helperText('Upload RFQ documents, specifications, etc.')
                   ])
           ]);
       }
       
       public static function table(Table $table): Table
       {
           return $table
               ->columns([
                   TextColumn::make('title')
                       ->searchable()
                       ->sortable()
                       ->limit(30),
                       
                   TextColumn::make('creator.name')
                       ->label('Created By')
                       ->sortable(),
                       
                   TextColumn::make('status')
                       ->badge()
                       ->color(fn (string $state): string => match ($state) {
                           'draft' => 'gray',
                           'published' => 'success',
                           'closed' => 'warning',
                           'awarded' => 'primary',
                       }),
                       
                   TextColumn::make('budget_range')
                       ->label('Budget Range')
                       ->prefix('$'),
                       
                   TextColumn::make('starts_at')
                       ->dateTime()
                       ->sortable()
                       ->toggleable(),
                       
                   TextColumn::make('ends_at')
                       ->dateTime()
                       ->sortable()
                       ->toggleable(),
                       
                   TextColumn::make('responses_count')
                       ->label('Responses')
                       ->counts('responses')
                       ->badge()
                       ->color('primary'),
                       
                   TextColumn::make('created_at')
                       ->dateTime()
                       ->sortable()
                       ->toggleable(isToggledHiddenByDefault: true),
               ])
               ->filters([
                   SelectFilter::make('status')
                       ->options([
                           'draft' => 'Draft',
                           'published' => 'Published',
                           'closed' => 'Closed',
                           'awarded' => 'Awarded'
                       ]),
                   Filter::make('active')
                       ->query(fn (Builder $query): Builder => $query->active()),
                   Filter::make('expired')
                       ->query(fn (Builder $query): Builder => $query->where('ends_at', '<', now())),
               ])
               ->actions([
                   Tables\Actions\ViewAction::make(),
                   Tables\Actions\EditAction::make(),
                   
                   Tables\Actions\Action::make('publish')
                       ->icon('heroicon-o-megaphone')
                       ->color('success')
                       ->visible(fn ($record) => $record->status === 'draft')
                       ->action(function ($record) {
                           $record->update(['status' => 'published']);
                           
                           // Send invitations to vendors
                           $rfqService = app(RFQService::class);
                           $rfqService->publishRFQ($record);
                           
                           Notification::make()
                               ->title('RFQ published successfully')
                               ->success()
                               ->send();
                       }),
                       
                   Tables\Actions\Action::make('close')
                       ->icon('heroicon-o-lock-closed')
                       ->color('warning')
                       ->visible(fn ($record) => $record->status === 'published')
                       ->requiresConfirmation()
                       ->action(function ($record) {
                           $record->update(['status' => 'closed']);
                       }),
                       
                   Tables\Actions\Action::make('view_responses')
                       ->icon('heroicon-o-clipboard-document-check')
                       ->color('primary')
                       ->url(fn ($record) => RFQResponseResource::getUrl('index', ['rfq_id' => $record->id]))
                       ->visible(fn ($record) => $record->responses_count > 0),
               ])
               ->bulkActions([
                   Tables\Actions\BulkActionGroup::make([
                       Tables\Actions\DeleteBulkAction::make(),
                   ]),
               ]);
       }
       
       public static function getRelations(): array
       {
           return [
               RFQResource\RelationManagers\ResponsesRelationManager::class,
               RFQResource\RelationManagers\VendorsRelationManager::class,
           ];
       }
   }
   ```

3. **Create RFQResponse Resource**
   ```bash
   php artisan make:filament-resource RFQResponse --generate
   ```

4. **Create Relation Managers**
   ```bash
   php artisan make:filament-relation-manager RFQResource responses RFQResponse
   php artisan make:filament-relation-manager RFQResource vendors Vendor
   ```

#### Expected Output:
- Fully functional RFQ resource with comprehensive forms
- RFQResponse resource for bid management
- Relation managers for RFQ-vendor and RFQ-response relationships
- Publishing and closing workflows integrated
- Vendor invitation system functional

### 4. RFQ Services and Business Logic
**Estimated Time:** 2 days

#### Tasks:
1. **Create RFQService**
   ```php
   class RFQService
   {
       public function publishRFQ(RFQ $rfq): void
       {
           DB::transaction(function () use ($rfq) {
               // Update status
               $rfq->update(['status' => 'published']);
               
               // Send invitations to all attached vendors
               $this->sendVendorInvitations($rfq);
               
               // Log activity
               activity('rfq_published')
                   ->performedOn($rfq)
                   ->causedBy(auth()->user())
                   ->log('RFQ published: ' . $rfq->title);
           });
       }
       
       public function inviteVendors(RFQ $rfq, array $vendorIds): void
       {
           foreach ($vendorIds as $vendorId) {
               // Attach vendor if not already invited
               if (!$rfq->vendors()->where('vendor_id', $vendorId)->exists()) {
                   $rfq->vendors()->attach($vendorId, [
                       'invited_at' => now(),
                       'status' => 'invited'
                   ]);
                   
                   // Send individual invitation
                   $vendor = Vendor::find($vendorId);
                   if ($vendor && $vendor->user) {
                       $vendor->user->notify(new RFQInvitation($rfq, $vendor));
                   }
               }
           }
       }
       
       public function closeRFQ(RFQ $rfq): void
       {
           DB::transaction(function () use ($rfq) {
               $rfq->update(['status' => 'closed']);
               
               // Notify all vendors who submitted responses
               $this->notifyVendorsOfClosure($rfq);
               
               activity('rfq_closed')
                   ->performedOn($rfq)
                   ->causedBy(auth()->user())
                   ->log('RFQ closed: ' . $rfq->title);
           });
       }
       
       public function submitResponse(RFQ $rfq, Vendor $vendor, array $data): RFQResponse
       {
           return DB::transaction(function () use ($rfq, $vendor, $data) {
               // Create response
               $response = RFQResponse::create([
                   'rfq_id' => $rfq->id,
                   'vendor_id' => $vendor->id,
                   'quoted_amount' => $data['quoted_amount'],
                   'delivery_time_days' => $data['delivery_time_days'],
                   'technical_proposal' => $data['technical_proposal'],
                   'commercial_proposal' => $data['commercial_proposal'],
                   'line_items' => $data['line_items'] ?? null,
                   'submitted_at' => now(),
                   'status' => 'submitted'
               ]);
               
               // Update vendor invitation status
               $rfq->vendors()->updateExistingPivot($vendor->id, [
                   'status' => 'responded',
                   'responded_at' => now()
               ]);
               
               // Notify procurement officers
               $this->notifyProcurementOffers($rfq, $response);
               
               activity('rfq_response_submitted')
                   ->performedOn($response)
                   ->causedBy($vendor->user)
                   ->log('Response submitted for RFQ: ' . $rfq->title);
                   
               return $response;
           });
       }
       
       public function evaluateResponse(RFQResponse $response, User $evaluator, array $evaluation): RFQEvaluation
       {
           $totalScore = $this->calculateEvaluationScore($evaluation['criteria_scores'], $response->rfq->evaluation_criteria);
           
           return RFQEvaluation::create([
               'rfq_response_id' => $response->id,
               'evaluator_id' => $evaluator->id,
               'criteria_scores' => $evaluation['criteria_scores'],
               'total_score' => $totalScore,
               'comments' => $evaluation['comments'],
               'recommend_award' => $evaluation['recommend_award'] ?? false
           ]);
       }
       
       public function awardContract(RFQ $rfq, RFQResponse $winningResponse): void
       {
           DB::transaction(function () use ($rfq, $winningResponse) {
               // Update RFQ status
               $rfq->update(['status' => 'awarded']);
               
               // Update winning response
               $winningResponse->update(['status' => 'awarded']);
               
               // Update other responses as rejected
               $rfq->responses()
                   ->where('id', '!=', $winningResponse->id)
                   ->update(['status' => 'rejected']);
               
               // Notify all vendors of the decision
               $this->notifyVendorsOfAward($rfq, $winningResponse);
               
               activity('rfq_awarded')
                   ->performedOn($rfq)
                   ->causedBy(auth()->user())
                   ->withProperties(['winning_vendor' => $winningResponse->vendor->company_name])
                   ->log('RFQ awarded to: ' . $winningResponse->vendor->company_name);
           });
       }
       
       private function calculateEvaluationScore(array $criteriaScores, array $criteria): float
       {
           $totalScore = 0;
           $totalWeight = 0;
           
           foreach ($criteria as $criterion => $weight) {
               if (isset($criteriaScores[$criterion])) {
                   $totalScore += $criteriaScores[$criterion] * ($weight / 100);
                   $totalWeight += $weight / 100;
               }
           }
           
           return $totalWeight > 0 ? $totalScore / $totalWeight : 0;
       }
       
       private function sendVendorInvitations(RFQ $rfq): void
       {
           $rfq->vendors->each(function ($vendor) use ($rfq) {
               if ($vendor->user) {
                   $vendor->user->notify(new RFQInvitation($rfq, $vendor));
               }
           });
       }
       
       private function notifyProcurementOffers(RFQ $rfq, RFQResponse $response): void
       {
           $procurementOfficers = User::role('procurement_officer')->get();
           Notification::send($procurementOfficers, new NewRFQResponse($rfq, $response));
       }
       
       private function notifyVendorsOfClosure(RFQ $rfq): void
       {
           $respondedVendors = $rfq->respondedVendors;
           $respondedVendors->each(function ($vendor) use ($rfq) {
               if ($vendor->user) {
                   $vendor->user->notify(new RFQClosed($rfq));
               }
           });
       }
       
       private function notifyVendorsOfAward(RFQ $rfq, RFQResponse $winningResponse): void
       {
           $rfq->vendors->each(function ($vendor) use ($rfq, $winningResponse) {
               if ($vendor->user) {
                   $isWinner = $vendor->id === $winningResponse->vendor_id;
                   $vendor->user->notify(new RFQAwarded($rfq, $isWinner));
               }
           });
       }
   }
   ```

2. **Create RFQEvaluationService**
   ```php
   class RFQEvaluationService
   {
       public function generateComparisonReport(RFQ $rfq): array
       {
           $responses = $rfq->responses()
               ->with(['vendor', 'evaluations'])
               ->where('status', '!=', 'rejected')
               ->get();
               
           return [
               'rfq' => $rfq,
               'responses' => $responses->map(function ($response) {
                   return [
                       'vendor' => $response->vendor,
                       'quoted_amount' => $response->quoted_amount,
                       'delivery_time' => $response->delivery_time_days,
                       'evaluation_score' => $response->average_evaluation_score,
                       'technical_score' => $this->getTechnicalScore($response),
                       'commercial_score' => $this->getCommercialScore($response),
                       'total_evaluations' => $response->evaluations->count(),
                       'recommended' => $response->evaluations->where('recommend_award', true)->count()
                   ];
               }),
               'summary' => [
                   'total_responses' => $responses->count(),
                   'lowest_quote' => $responses->min('quoted_amount'),
                   'highest_quote' => $responses->max('quoted_amount'),
                   'average_quote' => $responses->avg('quoted_amount'),
                   'shortest_delivery' => $responses->min('delivery_time_days'),
                   'longest_delivery' => $responses->max('delivery_time_days')
               ]
           ];
       }
       
       public function recommendWinner(RFQ $rfq): ?RFQResponse
       {
           return $rfq->responses()
               ->whereHas('evaluations', function ($q) {
                   $q->where('recommend_award', true);
               })
               ->orderByDesc(function ($q) {
                   $q->selectRaw('AVG(total_score)')
                     ->from('rfq_evaluations')
                     ->whereColumn('rfq_response_id', 'rfq_responses.id');
               })
               ->first();
       }
       
       private function getTechnicalScore(RFQResponse $response): ?float
       {
           return $response->evaluations()
               ->whereJsonContains('criteria_scores->technical', '!=', null)
               ->avg('criteria_scores->technical');
       }
       
       private function getCommercialScore(RFQResponse $response): ?float
       {
           return $response->evaluations()
               ->whereJsonContains('criteria_scores->commercial', '!=', null)
               ->avg('criteria_scores->commercial');
       }
   }
   ```

#### Expected Output:
- Comprehensive RFQService handling complete RFQ lifecycle
- RFQEvaluationService for bid comparison and evaluation
- Automated notification system for all stakeholders
- Activity logging for audit trails
- Winner recommendation algorithm

### 5. Notifications System
**Estimated Time:** 1 day

#### Tasks:
1. **Create Notification Classes**
   ```bash
   php artisan make:notification RFQInvitation
   php artisan make:notification NewRFQResponse
   php artisan make:notification RFQClosed
   php artisan make:notification RFQAwarded
   ```

2. **Implement RFQ Notifications**
   ```php
   class RFQInvitation extends Notification
   {
       use Queueable;
       
       protected $rfq;
       protected $vendor;
       
       public function __construct(RFQ $rfq, Vendor $vendor)
       {
           $this->rfq = $rfq;
           $this->vendor = $vendor;
       }
       
       public function via($notifiable): array
       {
           return ['mail', 'database'];
       }
       
       public function toMail($notifiable): MailMessage
       {
           return (new MailMessage)
               ->subject('RFQ Invitation: ' . $this->rfq->title)
               ->line('You have been invited to participate in an RFQ.')
               ->line('RFQ Title: ' . $this->rfq->title)
               ->line('Submission Deadline: ' . $this->rfq->ends_at->format('M j, Y g:i A'))
               ->action('View RFQ Details', url('/vendor/rfqs/' . $this->rfq->slug))
               ->line('Please submit your response before the deadline.');
       }
       
       public function toArray($notifiable): array
       {
           return [
               'type' => 'rfq_invitation',
               'rfq_id' => $this->rfq->id,
               'rfq_title' => $this->rfq->title,
               'deadline' => $this->rfq->ends_at,
               'message' => 'You have been invited to participate in RFQ: ' . $this->rfq->title
           ];
       }
   }
   
   class NewRFQResponse extends Notification
   {
       use Queueable;
       
       protected $rfq;
       protected $response;
       
       public function __construct(RFQ $rfq, RFQResponse $response)
       {
           $this->rfq = $rfq;
           $this->response = $response;
       }
       
       public function via($notifiable): array
       {
           return ['mail', 'database'];
       }
       
       public function toMail($notifiable): MailMessage
       {
           return (new MailMessage)
               ->subject('New RFQ Response: ' . $this->rfq->title)
               ->line('A new response has been submitted for RFQ: ' . $this->rfq->title)
               ->line('Vendor: ' . $this->response->vendor->company_name)
               ->line('Quoted Amount: $' . number_format($this->response->quoted_amount, 2))
               ->line('Delivery Time: ' . $this->response->delivery_time_days . ' days')
               ->action('Review Response', url('/admin/rfq-responses/' . $this->response->id))
               ->line('Please review and evaluate this response.');
       }
       
       public function toArray($notifiable): array
       {
           return [
               'type' => 'new_rfq_response',
               'rfq_id' => $this->rfq->id,
               'response_id' => $this->response->id,
               'vendor_name' => $this->response->vendor->company_name,
               'quoted_amount' => $this->response->quoted_amount,
               'message' => 'New response submitted by ' . $this->response->vendor->company_name
           ];
       }
   }
   ```

#### Expected Output:
- Complete notification system for RFQ lifecycle events
- Email and database notifications implemented
- Automated notifications for vendors and procurement officers
- Notification templates with relevant RFQ information

### 6. Policies and Authorization
**Estimated Time:** 1 day

#### Tasks:
1. **Create RFQ Policy**
   ```bash
   php artisan make:policy RFQPolicy --model=RFQ
   php artisan make:policy RFQResponsePolicy --model=RFQResponse
   ```

2. **Implement Authorization Rules**
   ```php
   class RFQPolicy
   {
       public function viewAny(User $user): bool
       {
           return $user->can('view_rfqs');
       }
       
       public function view(User $user, RFQ $rfq): bool
       {
           if ($user->can('view_rfqs')) return true;
           
           // Vendors can only view RFQs they're invited to
           if ($user->hasRole('vendor')) {
               return $rfq->vendors()->where('user_id', $user->id)->exists();
           }
           
           return false;
       }
       
       public function create(User $user): bool
       {
           return $user->can('create_rfqs');
       }
       
       public function update(User $user, RFQ $rfq): bool
       {
           if (!$user->can('manage_rfqs')) return false;
           
           // Can only update draft RFQs or if user is creator
           return $rfq->status === 'draft' || $user->id === $rfq->created_by;
       }
       
       public function publish(User $user, RFQ $rfq): bool
       {
           return $user->can('manage_rfqs') && $rfq->status === 'draft';
       }
       
       public function close(User $user, RFQ $rfq): bool
       {
           return $user->can('manage_rfqs') && $rfq->status === 'published';
       }
       
       public function award(User $user, RFQ $rfq): bool
       {
           return $user->can('manage_rfqs') && $rfq->status === 'closed';
       }
   }
   
   class RFQResponsePolicy
   {
       public function viewAny(User $user): bool
       {
           return $user->can('view_rfqs') || $user->hasRole('vendor');
       }
       
       public function view(User $user, RFQResponse $response): bool
       {
           if ($user->can('view_rfqs')) return true;
           
           // Vendors can only view their own responses
           if ($user->hasRole('vendor')) {
               return $response->vendor->user_id === $user->id;
           }
           
           return false;
       }
       
       public function create(User $user): bool
       {
           return $user->hasRole('vendor');
       }
       
       public function update(User $user, RFQResponse $response): bool
       {
           // Vendors can only update their own responses if RFQ is still active
           if ($user->hasRole('vendor')) {
               return $response->vendor->user_id === $user->id 
                   && $response->rfq->canReceiveResponses()
                   && $response->status === 'submitted';
           }
           
           return false;
       }
       
       public function evaluate(User $user, RFQResponse $response): bool
       {
           return $user->can('manage_rfqs') && $response->status !== 'rejected';
       }
   }
   ```

#### Expected Output:
- Comprehensive authorization policies for RFQ operations
- Role-based access control for vendors and procurement officers
- Proper separation of vendor and admin capabilities
- Status-based permissions for RFQ lifecycle stages

### 7. Testing Implementation
**Estimated Time:** 1.5 days

#### Tasks:
1. **Feature Tests for RFQ Operations**
   ```php
   class RFQManagementTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_procurement_officer_can_create_rfq()
       {
           $user = User::factory()->create();
           $user->assignRole('procurement_officer');
           
           $rfqData = [
               'title' => 'IT Services RFQ',
               'description' => 'Need IT consulting services',
               'starts_at' => now()->addDay(),
               'ends_at' => now()->addWeek(),
               'budget_min' => 10000,
               'budget_max' => 50000
           ];
           
           $this->actingAs($user)
                ->post('/admin/rfqs', $rfqData)
                ->assertRedirect();
                
           $this->assertDatabaseHas('rfqs', [
               'title' => 'IT Services RFQ',
               'created_by' => $user->id
           ]);
       }
       
       public function test_vendor_can_submit_response_to_invited_rfq()
       {
           $vendor = Vendor::factory()->create();
           $user = User::factory()->create();
           $vendor->update(['user_id' => $user->id]);
           $user->assignRole('vendor');
           
           $rfq = RFQ::factory()->create(['status' => 'published']);
           $rfq->inviteVendors([$vendor->id]);
           
           $responseData = [
               'quoted_amount' => 25000,
               'delivery_time_days' => 30,
               'technical_proposal' => 'Our technical approach...',
               'commercial_proposal' => 'Our pricing structure...'
           ];
           
           $service = new RFQService();
           $response = $service->submitResponse($rfq, $vendor, $responseData);
           
           $this->assertEquals($vendor->id, $response->vendor_id);
           $this->assertEquals('submitted', $response->status);
       }
       
       public function test_rfq_evaluation_calculates_correct_score()
       {
           $response = RFQResponse::factory()->create();
           $response->rfq->update([
               'evaluation_criteria' => [
                   'technical' => 40,
                   'commercial' => 35,
                   'delivery' => 25
               ]
           ]);
           
           $evaluator = User::factory()->create();
           $service = new RFQService();
           
           $evaluation = $service->evaluateResponse($response, $evaluator, [
               'criteria_scores' => [
                   'technical' => 85,
                   'commercial' => 90,
                   'delivery' => 95
               ],
               'comments' => 'Excellent proposal',
               'recommend_award' => true
           ]);
           
           // Expected score: (85*0.4 + 90*0.35 + 95*0.25) / 1 = 89.25
           $this->assertEquals(89.25, $evaluation->total_score);
       }
   }
   ```

#### Expected Output:
- Comprehensive test coverage for RFQ lifecycle
- Tests for vendor response submission
- Evaluation scoring algorithm tests
- Authorization and policy tests
- Integration tests for notifications

## Quality Assurance Checklist

### Functional Tests
- [ ] Procurement officers can create and manage RFQs
- [ ] Vendor invitation system works correctly
- [ ] Vendors can submit responses to invited RFQs only
- [ ] RFQ publishing and closing workflows function properly
- [ ] Evaluation system calculates scores correctly
- [ ] Award process updates all relevant statuses
- [ ] Notifications sent to appropriate parties
- [ ] Document upload and management operational

### Security Tests
- [ ] Vendors cannot view RFQs they're not invited to
- [ ] Vendors cannot access other vendors' responses
- [ ] RFQ modification restrictions enforced by status
- [ ] Response submission restricted to active RFQs only
- [ ] File upload security measures in place

### Performance Tests
- [ ] RFQ listing page performs well with large datasets
- [ ] Vendor invitation process handles bulk operations
- [ ] Response comparison page loads efficiently
- [ ] Search and filtering functionality optimized

## Expected Deliverables

1. **Complete RFQ Management System**
   - Full RFQ lifecycle from creation to award
   - Vendor invitation and response system
   - Comprehensive evaluation and comparison tools

2. **Business Logic Implementation**
   - RFQService for complete lifecycle management
   - RFQEvaluationService for bid analysis
   - Automated scoring and recommendation system

3. **Notification System**
   - Email and in-app notifications for all stakeholders
   - Event-driven notification triggers
   - Customizable notification templates

4. **Authorization Framework**
   - Role-based access control for RFQ operations
   - Status-based permission enforcement
   - Vendor self-service capabilities

5. **Testing Suite**
   - Feature tests covering complete RFQ workflows
   - Unit tests for evaluation algorithms
   - Integration tests for notification system

## Success Criteria

✅ **Phase 2 Complete When:**
1. Procurement officers can successfully manage complete RFQ lifecycle
2. Vendors can respond to RFQs they're invited to
3. Evaluation and comparison system provides accurate scoring
4. Award process correctly updates all related entities
5. Notification system reaches all appropriate parties
6. All authorization policies properly enforced
7. All tests passing with minimum 80% coverage
8. System ready for contract management (Phase 3)

## Next Phase Preparation

**Preparation for Phase 3 (Contract Management):**
- RFQ award process should create contract placeholders
- Vendor and RFQ data should be available for contract creation
- Document management should support contract files
- Notification system should be ready for contract workflows

**Dependencies for Next Phase:**
- Awarded RFQs available for contract creation
- Vendor management system fully operational
- Document upload and storage system functional
- User roles configured for contract management

---

**Dependencies:** Phase 0 (Foundation), Phase 1 (Vendor Management)  
**Risks:** Complex evaluation algorithms, notification delivery issues, vendor response security  
**Mitigation:** Thorough testing of scoring logic, email delivery monitoring, security audit of file uploads
