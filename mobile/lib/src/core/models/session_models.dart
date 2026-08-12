typedef JsonMap = Map<String, dynamic>;

class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    required this.platformRole,
    required this.organizationRole,
    required this.theme,
    this.avatarUrl,
    this.phone,
    this.bio,
    this.primaryGoal,
  });

  factory AppUser.fromJson(JsonMap json) => AppUser(
    id: json['id'] as int,
    name: json['name'] as String? ?? 'Throughline user',
    email: json['email'] as String? ?? '',
    platformRole: json['platform_role'] as String? ?? '',
    organizationRole: json['organization_role'] as String?,
    theme: json['theme_preference'] as String? ?? 'system',
    avatarUrl: json['avatar_url'] as String?,
    phone: json['phone'] as String?,
    bio: json['bio'] as String?,
    primaryGoal: json['primary_goal'] as String?,
  );

  final int id;
  final String name;
  final String email;
  final String platformRole;
  final String? organizationRole;
  final String theme;
  final String? avatarUrl;
  final String? phone;
  final String? bio;
  final String? primaryGoal;

  bool get isAthlete => organizationRole == 'athlete';
  bool get isCoach => organizationRole == 'coach';
  bool get supportsMobile => isAthlete || isCoach;
}

class AppOrganization {
  const AppOrganization({
    required this.id,
    required this.name,
    required this.role,
    required this.timezone,
  });

  factory AppOrganization.fromJson(JsonMap json) => AppOrganization(
    id: json['id'] as int,
    name: json['name'] as String? ?? 'Organization',
    role: json['role'] as String?,
    timezone: json['timezone'] as String? ?? 'UTC',
  );

  final int id;
  final String name;
  final String? role;
  final String timezone;
}

extension JsonRead on JsonMap {
  JsonMap object(String key) =>
      (this[key] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};

  List<JsonMap> maps(String key) => (this[key] as List? ?? const [])
      .whereType<Map>()
      .map((value) => value.cast<String, dynamic>())
      .toList();

  String text(String key, [String fallback = '']) =>
      this[key]?.toString() ?? fallback;
}
