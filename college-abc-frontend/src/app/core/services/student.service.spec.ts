import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { StudentService } from './student.service';
import { Student } from '../models/student.model';
import { environment } from '../../../environments/environment';

describe('StudentService', () => {
  let service: StudentService;
  let httpMock: HttpTestingController;

  const mockStudent: Student = {
    id: '1',
    matricule: 'STD-2024-0001',
    firstName: 'Jean',
    lastName: 'KABORE',
    dateOfBirth: '2010-05-15',
    placeOfBirth: 'Ouagadougou',
    gender: 'M',
    address: 'Secteur 12',
    status: 'active',
    currentClass: '6ème A',
    parentName: 'Paul KABORE',
    parentPhone: '+226 70 00 00 01'
  };

  const mockApiResponse = {
    success: true,
    data: [{
      id: 1,
      matricule: 'STD-2024-0001',
      first_name: 'Jean',
      last_name: 'KABORE',
      date_of_birth: '2010-05-15',
      place_of_birth: 'Ouagadougou',
      gender: 'M',
      address: 'Secteur 12',
      status: 'active',
      current_enrollment: {
        class_room: { name: '6ème A' }
      },
      parents: [{
        first_name: 'Paul',
        last_name: 'KABORE',
        phone: '+226 70 00 00 01'
      }]
    }],
    meta: {
      current_page: 1,
      last_page: 1,
      total: 1
    }
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [StudentService]
    });

    service = TestBed.inject(StudentService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  describe('CRUD Operations', () => {
    it('should be created', () => {
      expect(service).toBeTruthy();
    });

    it('should get students list', () => {
      service.getStudents().subscribe(students => {
        expect(students).toHaveSize(1);
        expect(students[0].firstName).toBe('Jean');
        expect(students[0].lastName).toBe('KABORE');
        expect(students[0].currentClass).toBe('6ème A');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      expect(req.request.method).toBe('GET');
      req.flush(mockApiResponse);
    });

    it('should get single student', () => {
      const studentId = '1';

      service.getStudent(studentId).subscribe(student => {
        expect(student).toBeDefined();
        expect(student?.firstName).toBe('Jean');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students/${studentId}`);
      expect(req.request.method).toBe('GET');
      req.flush({ success: true, data: mockApiResponse.data[0] });
    });

    it('should add new student', () => {
      const newStudent: Student = {
        firstName: 'Marie',
        lastName: 'OUEDRAOGO',
        dateOfBirth: '2011-03-20',
        placeOfBirth: 'Bobo-Dioulasso',
        gender: 'F',
        address: 'Secteur 15',
        status: 'active'
      };

      service.addStudent(newStudent).subscribe(student => {
        expect(student).toBeDefined();
        expect(student.firstName).toBe('Marie');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(newStudent);
      req.flush({ 
        success: true, 
        data: { ...newStudent, id: '2', matricule: 'STD-2024-0002' } 
      });
    });

    it('should update student', () => {
      const studentId = '1';
      const updates = { firstName: 'Jean-Baptiste' };

      service.updateStudent(studentId, updates).subscribe(student => {
        expect(student).toBeDefined();
        expect(student?.firstName).toBe('Jean-Baptiste');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students/${studentId}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updates);
      req.flush({ 
        success: true, 
        data: { ...mockApiResponse.data[0], first_name: 'Jean-Baptiste' } 
      });
    });

    it('should delete student', () => {
      const studentId = '1';

      service.deleteStudent(studentId).subscribe(result => {
        expect(result).toBe(true);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students/${studentId}`);
      expect(req.request.method).toBe('DELETE');
      req.flush({ success: true });
    });
  });

  describe('Class-specific Operations', () => {
    it('should get students by class', () => {
      const classId = 1;

      service.getStudentsByClass(classId).subscribe(students => {
        expect(students).toBeDefined();
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/classes/${classId}/students`);
      expect(req.request.method).toBe('GET');
      req.flush({ success: true, data: mockApiResponse.data });
    });
  });

  describe('Error Handling', () => {
    it('should handle HTTP errors', () => {
      service.getStudents().subscribe({
        next: () => fail('Should have failed'),
        error: (error) => {
          expect(error.status).toBe(500);
        }
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      req.flush({ message: 'Server error' }, { status: 500, statusText: 'Internal Server Error' });
    });

    it('should handle empty response', () => {
      service.getStudents().subscribe(students => {
        expect(students).toEqual([]);
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      req.flush({ success: true, data: [] });
    });
  });

  describe('Data Transformation', () => {
    it('should correctly transform API response to Student model', () => {
      service.getStudents().subscribe(students => {
        const student = students[0];
        
        // Check field mapping
        expect(student.firstName).toBe('Jean'); // first_name -> firstName
        expect(student.lastName).toBe('KABORE'); // last_name -> lastName
        expect(student.dateOfBirth).toBe('2010-05-15'); // date_of_birth -> dateOfBirth
        expect(student.currentClass).toBe('6ème A'); // nested relation
        expect(student.parentName).toBe('Paul KABORE'); // computed from parent data
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      req.flush(mockApiResponse);
    });

    it('should handle missing nested relations', () => {
      const responseWithoutRelations = {
        success: true,
        data: [{
          id: 1,
          matricule: 'STD-2024-0001',
          first_name: 'Jean',
          last_name: 'KABORE',
          date_of_birth: '2010-05-15',
          gender: 'M',
          status: 'active'
          // No current_enrollment or parents
        }]
      };

      service.getStudents().subscribe(students => {
        const student = students[0];
        expect(student.currentClass).toBe('Non inscrit');
        expect(student.parentName).toBe('');
      });

      const req = httpMock.expectOne(`${environment.apiUrl}/v1/students`);
      req.flush(responseWithoutRelations);
    });
  });
});