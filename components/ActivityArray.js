export default class ActivityArray {

  activities = new Array();
  summary = {};
  
  constructor(array) {
    this.activities = new Array();
    this.summary = {};
    if (array) {
      this.activities.concat(array);
      this.calculateSummary();
    }
  }
  
  initialSummary(sport) {
    return {
      sport: sport,
      activities: 0,
      averageHeartRateBpm: 0,
      averageHeartRateBpmSeconds: 0, // temp to calculate rate
      averageSpeed: 0,
      averageSpeedSeconds: 0,
      calories: 0,
      distanceMeters: 0,
      maximumHeartRateBpm: 0,
      maximumSpeed: 0,
      totalTimeSeconds: 0
    };
  }
  
  calculateSum(magnitude, activity, summary) {
    if (activity[magnitude] && activity[magnitude] !== 0) {
      summary[magnitude] += activity[magnitude];
    }
  }
  
  calculateMax(magnitude, activity, summary) {
    if (activity[magnitude] && activity[magnitude] !== 0) {
      if (activity[magnitude] > summary[magnitude]) {
        summary[magnitude] = activity[magnitude];
      }
    }
  }
  
  calculateRateInternal(magnitude, activity, summary, value) {
    if (value !== 0 && activity['totalTimeSeconds'] && activity['totalTimeSeconds'] !== 0) {
      summary[magnitude] += (value * activity['totalTimeSeconds']);
      summary[magnitude + 'Seconds'] += activity['totalTimeSeconds'];
    }
  }
  
  calculateRate(magnitude, activity, summary) {
    if (activity[magnitude]) {
      this.calculateRateInternal(magnitude, activity, summary, activity[magnitude]);
    }
  }
  
  calculateSummary() {
    this.summary = {};
    for (var act in this.activities) {
      if (this.activities[act].sport) {
        if (!this.summary[this.activities[act].sport]) {
          this.summary[this.activities[act].sport] = this.initialSummary(this.activities[act].sport);
        }
        this.summary[this.activities[act].sport].activities += 1;
        ['calories', 'distanceMeters', 'totalTimeSeconds'].forEach(magnitude =>
          this.calculateSum(magnitude, this.activities[act], this.summary[this.activities[act].sport]));
        ['maximumHeartRateBpm', 'maximumSpeed'].forEach(magnitude =>
          this.calculateMax(magnitude, this.activities[act], this.summary[this.activities[act].sport]));
        ['averageHeartRateBpm'].forEach(magnitude =>
          this.calculateRate(magnitude, this.activities[act], this.summary[this.activities[act].sport]));
        if (this.activities[act]['distanceMeters'] && this.activities[act]['distanceMeters'] != 0) {
          this.calculateRateInternal('averageSpeed', this.activities[act], this.summary[this.activities[act].sport], 
            this.activities[act].distanceMeters / this.activities[act].totalTimeSeconds);
        }
      }
    }
    // calculate the rates
    Object.keys(this.summary).forEach(sport => {
      ['averageHeartRateBpm', 'averageSpeed'].forEach(magnitude => {
        if (this.summary[sport][magnitude] !== 0 && this.summary[sport][magnitude + 'Seconds'] !== 0) {
          this.summary[sport][magnitude] = this.summary[sport][magnitude] / this.summary[sport][magnitude + 'Seconds'];
          delete this.summary[sport][magnitude + 'Seconds'];
        }
      })
    });
  }
  
  getActivities() {
    return this.activities;
  }
  
  concat(moreActivities) {
    this.activities = this.activities.concat(moreActivities);
    this.calculateSummary();
  }
  
  get(idx) {
    return this.activities[idx];
  }
  
  size() {
    return this.activities.length;
  }
}