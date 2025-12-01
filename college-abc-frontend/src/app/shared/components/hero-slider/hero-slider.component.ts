import { Component, Input, OnInit, OnDestroy, signal } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-hero-slider',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="absolute inset-0 z-0 bg-gray-900 overflow-hidden">
      @for (image of images; track i; let i = $index) {
        <div class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
             [class.opacity-100]="currentSlide() === i"
             [class.opacity-0]="currentSlide() !== i"
             [class.z-10]="currentSlide() === i"
             [class.z-0]="currentSlide() !== i">
          <img [src]="image" alt="Hero Background" class="w-full h-full object-cover opacity-60" />
        </div>
      }
      <!-- Overlay Gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-black/30 z-10"></div>
    </div>
  `
})
export class HeroSliderComponent implements OnInit, OnDestroy {
  @Input() images: string[] = [];
  @Input() interval = 5000;
  @Input() overlayOpacity = 60; // Default opacity for overlay

  currentSlide = signal(0);
  private intervalId: any;

  ngOnInit() {
    if (this.images.length > 1) {
      this.startSlider();
    }
  }

  ngOnDestroy() {
    this.stopSlider();
  }

  private startSlider() {
    this.intervalId = setInterval(() => {
      this.currentSlide.update(current => (current + 1) % this.images.length);
    }, this.interval);
  }

  private stopSlider() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
  }
}
