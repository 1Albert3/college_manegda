# Modèles TypeScript pour l'Application Frontend

Ce document contient toutes les interfaces TypeScript nécessaires pour l'intégration avec l'API backend Laravel de gestion scolaire.

## Table des matières

1. [Types de base](#types-de-base)
2. [Interface de réponse API](#interface-de-réponse-api)
3. [Authentification](#authentification)
4. [Utilisateurs](#utilisateurs)
5. [Étudiants](#étudiants)
6. [Années académiques](#années-académiques)
7. [Matières](#matières)
8. [Classes](#classes)
9. [Évaluations](#évaluations)
10. [Notes](#notes)
11. [Inscriptions](#inscriptions)
12. [Pagination](#pagination)
13. [Filtres et recherche](#filtres-et-recherche)

## Types de base

```typescript
// Types primitifs utilisés dans l'application
type UUID = string;
type DateString = string;
type Decimal = number;
type Gender = 'M' | 'F';
type Status = 'active' | 'inactive' | 'suspended' | 'graduated' | 'withdrawn';
type AcademicStatus = 'planned' | 'active' | 'completed' | 'cancelled';
type EvaluationType = 'continuous' | 'semester' | 'annual';
type EvaluationStatus = 'planned' | 'ongoing' | 'completed' | 'cancelled';
type GradeLetter = 'A+' | 'A' | 'B+' | 'B' | 'C+' | 'C' | 'D+' | 'D' | 'F';
type RelationshipType = 'father' | 'mother' | 'guardian' | 'other';

// Types utilitaires
type Optional<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>;
type WithTimestamps<T> = T & {
  created_at: DateString;
  updated_at: DateString;
};
```

## Interface de réponse API

```typescript
export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data: T;
  errors?: Record<string, string[]>;
}

// Types spécifiques pour les réponses communes
export type ApiSuccessResponse<T> = ApiResponse<T> & { success: true };
export type ApiErrorResponse = ApiResponse<null> & { success: false; errors?: Record<string, string[]> };
```

## Authentification

```typescript
export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  role_type?: string;
  role?: string;
}

export interface AuthTokens {
  user: User;
  token: string;
}

export interface ChangePasswordRequest {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface ForgotPasswordRequest {
  email: string;
}
```

## Utilisateurs

```typescript
export interface User {
  id: UUID;
  name: string;
  email: string;
  phone?: string;
  role_type?: string;
  is_active: boolean;
  last_login_at?: DateString;
  email_verified_at?: DateString;
  profile_type?: string;
  profile_id?: UUID;
  roles?: Role[];
  profile?: Student | Teacher;
  created_at: DateString;
  updated_at: DateString;
}

export interface Role {
  id: UUID;
  name: string;
  guard_name: string;
  created_at: DateString;
  updated_at: DateString;
}

export interface Permission {
  id: UUID;
  name: string;
  guard_name: string;
  created_at: DateString;
  updated_at: DateString;
}

export interface Teacher {
  id: UUID;
  user_id: UUID;
  employee_id?: string;
  department?: string;
  specialization?: string;
  hire_date?: DateString;
  user: User;
  subjects: Subject[];
  evaluations: Evaluation[];
}
```

## Étudiants

```typescript
export interface Student {
  id: UUID;
  user_id: UUID;
  matricule: string;
  first_name: string;
  last_name: string;
  date_of_birth: DateString;
  gender: Gender;
  place_of_birth?: string;
  address?: string;
  photo?: string;
  status: Status;
  medical_info?: Record<string, any>;
  user: User;
  parents: Parent[];
  enrollments: Enrollment[];
  current_enrollment?: Enrollment;
  grades: Grade[];
  created_at: DateString;
  updated_at: DateString;
  deleted_at?: DateString;

  // Propriétés calculées
  full_name?: string;
  age?: number;
  current_class_name?: string;
  primary_parent?: Parent;
}

export interface Parent {
  id: UUID;
  user_id: UUID;
  student_id: UUID;
  relationship: RelationshipType;
  is_primary: boolean;
  user: User;
  created_at: DateString;
  updated_at: DateString;
}

export interface StudentStats {
  total_students: number;
  active_students: number;
  by_gender: Record<Gender, number>;
  by_status: Record<Status, number>;
  by_class: Record<string, number>;
}

export interface StudentReportCard {
  student: Student;
  subjects: Record<string, SubjectReport>;
  overall_average: number;
  is_passing: boolean;
  academic_year?: AcademicYear;
}

export interface SubjectReport {
  subject: string;
  grades: Grade[];
  average: number;
  teacher: string;
  coefficient: number;
}
```

## Années académiques

```typescript
export interface AcademicYear {
  id: UUID;
  name: string;
  start_date: DateString;
  end_date: DateString;
  status: AcademicStatus;
  is_current: boolean;
  description?: string;
  semesters?: Semester[];
  enrollments: Enrollment[];
  teachers: User[];
  subjects: Subject[];
  class_subjects: ClassSubject[];
  created_at: DateString;
  updated_at: DateString;

  // Propriétés calculées
  is_ongoing?: boolean;
  duration?: number;
  progress_percentage?: number;
}

export interface Semester {
  id: UUID;
  academic_year_id: UUID;
  name: string;
  start_date: DateString;
  end_date: DateString;
  order: number;
}

export interface AcademicYearStats {
  total_years: number;
  current_year?: AcademicYear;
  upcoming_years: AcademicYear[];
  completed_years: AcademicYear[];
}
```

## Matières

```typescript
export interface Subject {
  id: UUID;
  name: string;
  code?: string;
  description?: string;
  coefficient: number;
  color?: string;
  is_active: boolean;
  academic_years: AcademicYear[];
  teachers: User[];
  evaluations: Evaluation[];
  class_subjects: ClassSubject[];
  created_at: DateString;
  updated_at: DateString;
}

export interface TeacherSubject {
  id: UUID;
  teacher_id: UUID;
  subject_id: UUID;
  academic_year_id: UUID;
  teacher: User;
  subject: Subject;
  academic_year: AcademicYear;
  created_at: DateString;
  updated_at: DateString;
}
```

## Classes

```typescript
export interface ClassRoom {
  id: UUID;
  name: string;
  code?: string;
  level: string;
  capacity: number;
  description?: string;
  is_active: boolean;
  academic_year_id: UUID;
  academic_year: AcademicYear;
  students: Student[];
  evaluations: Evaluation[];
  class_subjects: ClassSubject[];
  created_at: DateString;
  updated_at: DateString;

  // Propriétés calculées
  student_count?: number;
  average_grade?: number;
}

export interface ClassSubject {
  id: UUID;
  class_id: UUID;
  subject_id: UUID;
  teacher_id: UUID;
  academic_year_id: UUID;
  class: ClassRoom;
  subject: Subject;
  teacher: User;
  academic_year: AcademicYear;
  evaluations: Evaluation[];
  created_at: DateString;
  updated_at: DateString;
}
```

## Évaluations

```typescript
export interface Evaluation {
  id: UUID;
  name: string;
  code?: string;
  description?: string;
  type: EvaluationType;
  period: string;
  coefficient: Decimal;
  weight_percentage: Decimal;
  academic_year_id: UUID;
  subject_id: UUID;
  class_id: UUID;
  teacher_id: UUID;
  evaluation_date: DateString;
  status: EvaluationStatus;
  maximum_score: Decimal;
  minimum_score: Decimal;
  grading_criteria?: Record<string, any>;
  comments?: string;
  academic_year: AcademicYear;
  subject: Subject;
  class: ClassRoom;
  teacher: User;
  grades: Grade[];
  created_at: DateString;
  updated_at: DateString;

  // Propriétés calculées
  is_completed?: boolean;
  is_ongoing?: boolean;
  is_past?: boolean;
  days_until?: number;
  has_grades?: boolean;
  completion_percentage?: number;
}

export interface EvaluationReport {
  evaluation: Evaluation;
  total_students: number;
  graded_students: number;
  average_grade: number;
  grade_distribution: Record<GradeLetter, number>;
  passing_rate: number;
  highest_grade: Grade;
  lowest_grade: Grade;
}

export interface EvaluationFilters {
  subject_id?: UUID;
  class_id?: UUID;
  teacher_id?: UUID;
  academic_year_id?: UUID;
  type?: EvaluationType;
  period?: string;
  status?: EvaluationStatus;
  date_from?: DateString;
  date_to?: DateString;
  search?: string;
}
```

## Notes

```typescript
export interface Grade {
  id: UUID;
  student_id: UUID;
  evaluation_id: UUID;
  score?: Decimal;
  coefficient: Decimal;
  weighted_score: Decimal;
  grade_letter: GradeLetter;
  is_absent: boolean;
  comments?: string;
  recorded_at: DateString;
  recorded_by: UUID;
  student: Student;
  evaluation: Evaluation;
  recorder: User;
  created_at: DateString;
  updated_at: DateString;
  deleted_at?: DateString;

  // Propriétés calculées
  is_passing?: boolean;
  grade_color?: string;
  grade_status?: string;
  formatted_score?: string;
  formatted_weighted_score?: string;
}

export interface GradeFilters {
  student_id?: UUID;
  evaluation_id?: UUID;
  academic_year_id?: UUID;
  subject_id?: UUID;
  class_id?: UUID;
  is_absent?: boolean;
  grade_from?: number;
  grade_to?: number;
  recorded_from?: DateString;
  recorded_to?: DateString;
  search?: string;
}

export interface BulkGradeRecord {
  grades: Array<{
    student_id: UUID;
    evaluation_id: UUID;
    score?: Decimal;
    coefficient?: Decimal;
    is_absent?: boolean;
    comments?: string;
  }>;
}

export interface GradeStats {
  total_grades: number;
  average_grade: number;
  passing_rate: number;
  grade_distribution: Record<GradeLetter, number>;
  by_subject: Record<string, number>;
  by_class: Record<string, number>;
}
```

## Inscriptions

```typescript
export interface Enrollment {
  id: UUID;
  student_id: UUID;
  academic_year_id: UUID;
  class_id: UUID;
  enrollment_date: DateString;
  status: Status;
  comments?: string;
  student: Student;
  academic_year: AcademicYear;
  class: ClassRoom;
  created_at: DateString;
  updated_at: DateString;
}

export interface EnrollmentRequest {
  student_id: UUID;
  academic_year_id: UUID;
  class_id: UUID;
  enrollment_date?: DateString;
  comments?: string;
}
```

## Communications

```typescript
// Statuts de communication
type CommunicationStatus = 'pending' | 'processing' | 'sent' | 'delivered' | 'failed' | 'cancelled';
type CommunicationChannel = 'email' | 'sms' | 'push' | 'in_app';
type CommunicationPriority = 'low' | 'normal' | 'high' | 'urgent';

// Log de communication
export interface CommunicationLog {
  id: UUID;
  channel: CommunicationChannel;
  provider?: string;
  recipient_type?: string;
  recipient_id?: UUID;
  recipient_address: string;
  template_name?: string;
  subject?: string;
  content: string;
  variables?: Record<string, any>;
  status: CommunicationStatus;
  error_message?: string;
  sent_at?: DateString;
  delivered_at?: DateString;
  opened_at?: DateString;
  clicked_at?: DateString;
  metadata?: Record<string, any>;
  priority: CommunicationPriority;
  attempts: number;
  max_attempts: number;
  next_retry_at?: DateString;
  batch_id?: string;
  user_id?: UUID;
  user?: User;
  created_at: DateString;
  updated_at: DateString;
  deleted_at?: DateString;
}

// Template de communication
export interface CommunicationTemplate {
  id: UUID;
  name: string;
  slug: string;
  description?: string;
  channel: CommunicationChannel;
  subject?: string;
  content: string;
  html_content?: string;
  variables?: Record<string, any>;
  is_active: boolean;
  category?: string;
  priority: CommunicationPriority;
  metadata?: Record<string, any>;
  created_by?: UUID;
  updated_by?: UUID;
  creator?: User;
  updater?: User;
  logs?: CommunicationLog[];
  created_at: DateString;
  updated_at: DateString;
}

// Statistiques de communication
export interface CommunicationStats {
  total: number;
  sent: number;
  failed: number;
  pending: number;
  delivery_rate: number;
  failure_rate: number;
}

// Filtres pour les logs
export interface CommunicationLogFilters extends BaseFilters {
  channel?: CommunicationChannel;
  status?: CommunicationStatus;
  template?: string;
  date_from?: DateString;
  date_to?: DateString;
}

// Filtres pour les templates
export interface CommunicationTemplateFilters extends BaseFilters {
  channel?: CommunicationChannel;
  category?: string;
  is_active?: boolean;
}

// Requêtes d'envoi
export interface SendCommunicationRequest {
  channel: CommunicationChannel;
  recipient: string;
  template: string;
  variables?: Record<string, any>;
  options?: {
    priority?: CommunicationPriority;
    batch_id?: string;
    metadata?: Record<string, any>;
  };
}

export interface SendToUserRequest {
  user_id: UUID;
  template: string;
  variables?: Record<string, any>;
  channels?: CommunicationChannel[];
  options?: {
    priority?: CommunicationPriority;
    batch_id?: string;
    metadata?: Record<string, any>;
  };
}

export interface SendBulkRequest {
  template: string;
  recipients: UUID[];
  channel?: CommunicationChannel;
  variables?: Record<string, any>;
  options?: {
    priority?: CommunicationPriority;
    batch_id?: string;
    metadata?: Record<string, any>;
  };
}

export interface TestChannelRequest {
  channel: CommunicationChannel;
  recipient: string;
}

// Réponses API
export interface TestChannelResponse extends ApiResponse<{
  success: boolean;
  message: string;
  log_id?: UUID;
}> {}

export interface CommunicationLogsResponse extends PaginatedApiResponse<CommunicationLog> {}

export interface CommunicationStatsResponse extends ApiResponse<CommunicationStats> {}

export interface CommunicationTemplatesResponse extends ApiResponse<CommunicationTemplate[]> {}
```

## Pagination

```typescript
export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface PaginationLinks {
  first: string;
  last: string;
  prev: string | null;
  next: string | null;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta;
  links: PaginationLinks;
}

// Type helper pour les réponses paginées
export type PaginatedApiResponse<T> = ApiResponse<{
  data: T[];
  meta: PaginationMeta;
  links: PaginationLinks;
}>;
```

## Filtres et recherche

```typescript
export interface BaseFilters {
  search?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface StudentFilters extends BaseFilters {
  status?: Status;
  gender?: Gender;
  matricule?: string;
  class_id?: UUID;
  academic_year_id?: UUID;
}

export interface UserFilters extends BaseFilters {
  role_type?: string;
  is_active?: boolean;
  email?: string;
  phone?: string;
}

export interface AcademicYearFilters extends BaseFilters {
  status?: AcademicStatus;
  is_current?: boolean;
}

export interface SubjectFilters extends BaseFilters {
  is_active?: boolean;
  code?: string;
}

export interface ClassRoomFilters extends BaseFilters {
  level?: string;
  is_active?: boolean;
  academic_year_id?: UUID;
}

// Types pour les requêtes de création/modification
export type CreateStudentRequest = Optional<Student, 'id' | 'created_at' | 'updated_at' | 'user' | 'parents' | 'enrollments' | 'grades'>;
export type UpdateStudentRequest = Partial<CreateStudentRequest>;

export type CreateEvaluationRequest = Optional<Evaluation, 'id' | 'created_at' | 'updated_at' | 'academic_year' | 'subject' | 'class' | 'teacher' | 'grades'>;
export type UpdateEvaluationRequest = Partial<CreateEvaluationRequest>;

export type CreateGradeRequest = Optional<Grade, 'id' | 'created_at' | 'updated_at' | 'student' | 'evaluation' | 'recorder' | 'weighted_score' | 'grade_letter'>;
export type UpdateGradeRequest = Partial<CreateGradeRequest>;
```

## Exemples d'utilisation

```typescript
// Exemple de réponse API typique
interface LoginResponse extends ApiResponse<AuthTokens> {}

// Exemple de liste paginée d'étudiants
interface StudentsResponse extends PaginatedApiResponse<Student> {}

// Exemple de requête avec filtres
const fetchStudents = async (filters: StudentFilters): Promise<StudentsResponse> => {
  const response = await api.get('/students', { params: filters });
  return response.data;
};

// Exemple de création d'évaluation
const createEvaluation = async (data: CreateEvaluationRequest): Promise<ApiResponse<Evaluation>> => {
  const response = await api.post('/evaluations', data);
  return response.data;
};
```

## Notes importantes

1. **UUIDs** : Tous les identifiants utilisent le format UUID (string)
2. **Dates** : Les dates sont représentées sous forme de chaînes ISO (YYYY-MM-DDTHH:mm:ssZ)
3. **Décimales** : Les valeurs décimales sont représentées par le type `number` en TypeScript
4. **Relations** : Les relations sont incluses selon les besoins via les paramètres `include` des requêtes
5. **Soft deletes** : Les entités supprimées logiquement ont un champ `deleted_at` optionnel
6. **Enums** : Les valeurs énumérées sont définies comme des types union pour la sécurité de type

Ce document doit être mis à jour lorsque de nouvelles entités ou champs sont ajoutés à l'API backend.
