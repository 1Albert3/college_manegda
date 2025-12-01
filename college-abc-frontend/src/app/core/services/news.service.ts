import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface NewsItem {
  id: number;
  title: string;
  date: string;
  category: string;
  imageUrl: string;
  excerpt: string;
}

export interface OfficialDocument {
  id: number;
  title: string;
  date: string;
  type: 'PDF' | 'DOC';
  size: string;
  downloadUrl: string;
}

@Injectable({
  providedIn: 'root'
})
export class NewsService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Récupère les dernières actualités.
   */
  getNews(): Observable<NewsItem[]> {
    return this.http.get<NewsItem[]>(`${this.apiUrl}/news`);
  }

  /**
   * Récupère les documents officiels publics.
   */
  getOfficialDocuments(): Observable<OfficialDocument[]> {
    return this.http.get<OfficialDocument[]>(`${this.apiUrl}/documents/official`);
  }
}
