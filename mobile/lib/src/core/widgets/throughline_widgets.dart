import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';

class ThroughlineMark extends StatelessWidget {
  const ThroughlineMark({super.key, this.compact = false});

  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: compact ? 38 : 48,
          height: compact ? 38 : 48,
          decoration: BoxDecoration(
            color: ThroughlineColors.lime,
            borderRadius: BorderRadius.circular(compact ? 13 : 16),
          ),
          child: const Icon(
            Icons.route_rounded,
            color: ThroughlineColors.graphite,
          ),
        ),
        const SizedBox(width: 12),
        Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'THROUGHLINE',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w900,
                letterSpacing: 1.6,
              ),
            ),
            if (!compact)
              Text(
                'COACHING OS',
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: ThroughlineColors.muted,
                  letterSpacing: 2,
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class ContentColumn extends StatelessWidget {
  const ContentColumn({
    super.key,
    required this.children,
    this.padding = const EdgeInsets.all(18),
  });

  final List<Widget> children;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) => ListView(
    padding: EdgeInsets.fromLTRB(
      padding.left,
      padding.top,
      padding.right,
      padding.bottom + 28,
    ),
    children: [
      ...children.expand((widget) => [widget, const SizedBox(height: 14)]),
    ],
  );
}

class PremiumCard extends StatelessWidget {
  const PremiumCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(20),
    this.accent,
  });

  final Widget child;
  final EdgeInsets padding;
  final Color? accent;

  @override
  Widget build(BuildContext context) => Card(
    child: Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: accent == null
            ? null
            : LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [accent!.withValues(alpha: 0.17), Colors.transparent],
              ),
      ),
      child: child,
    ),
  );
}

class PageIntro extends StatelessWidget {
  const PageIntro({
    super.key,
    required this.eyebrow,
    required this.title,
    required this.body,
    this.action,
  });

  final String eyebrow;
  final String title;
  final String body;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        eyebrow.toUpperCase(),
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          color: ThroughlineColors.lime,
          letterSpacing: 2.4,
          fontWeight: FontWeight.w800,
        ),
      ),
      const SizedBox(height: 8),
      Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(
              title,
              style: Theme.of(
                context,
              ).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900),
            ),
          ),
          if (action != null) ...[const SizedBox(width: 12), action!],
        ],
      ),
      const SizedBox(height: 7),
      Text(
        body,
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
          color: ThroughlineColors.muted,
          height: 1.45,
        ),
      ),
    ],
  );
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title, {super.key, this.action});

  final String title;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Expanded(
        child: Text(
          title,
          style: Theme.of(
            context,
          ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
        ),
      ),
      ?action,
    ],
  );
}

class MetricTile extends StatelessWidget {
  const MetricTile({
    super.key,
    required this.label,
    required this.value,
    required this.icon,
    this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color? color;

  @override
  Widget build(BuildContext context) => PremiumCard(
    padding: const EdgeInsets.all(16),
    child: Row(
      children: [
        CircleAvatar(
          radius: 20,
          backgroundColor: (color ?? ThroughlineColors.lime).withValues(
            alpha: 0.16,
          ),
          child: Icon(icon, color: color ?? ThroughlineColors.lime, size: 19),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: ThroughlineColors.muted,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class EmptyPanel extends StatelessWidget {
  const EmptyPanel({
    super.key,
    required this.title,
    required this.body,
    this.icon = Icons.inbox_outlined,
  });

  final String title;
  final String body;
  final IconData icon;

  @override
  Widget build(BuildContext context) => PremiumCard(
    child: Column(
      children: [
        Icon(icon, size: 38, color: ThroughlineColors.muted),
        const SizedBox(height: 12),
        Text(
          title,
          textAlign: TextAlign.center,
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17),
        ),
        const SizedBox(height: 6),
        Text(
          body,
          textAlign: TextAlign.center,
          style: const TextStyle(color: ThroughlineColors.muted, height: 1.4),
        ),
      ],
    ),
  );
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({super.key, required this.error, required this.onRetry});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final message = error is ApiFailure
        ? (error as ApiFailure).message
        : 'This section could not be loaded.';
    return PremiumCard(
      accent: ThroughlineColors.danger,
      child: Column(
        children: [
          const Icon(
            Icons.cloud_off_rounded,
            color: ThroughlineColors.danger,
            size: 34,
          ),
          const SizedBox(height: 10),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ],
      ),
    );
  }
}

class LoadingPanel extends StatelessWidget {
  const LoadingPanel({super.key});

  @override
  Widget build(BuildContext context) => const Center(
    child: Padding(
      padding: EdgeInsets.all(48),
      child: CircularProgressIndicator(),
    ),
  );
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'completed' || 'active' => ThroughlineColors.emerald,
      'partial' || 'scheduled' => ThroughlineColors.gold,
      'missed' => ThroughlineColors.danger,
      _ => ThroughlineColors.cyan,
    };
    return Chip(
      visualDensity: VisualDensity.compact,
      backgroundColor: color.withValues(alpha: 0.14),
      label: Text(
        status.toUpperCase(),
        style: TextStyle(color: color, fontSize: 10),
      ),
    );
  }
}

class AuthenticatedImage extends ConsumerWidget {
  const AuthenticatedImage({
    super.key,
    required this.url,
    this.fit = BoxFit.cover,
    this.borderRadius = const BorderRadius.all(Radius.circular(18)),
  });

  final String url;
  final BoxFit fit;
  final BorderRadius borderRadius;

  @override
  Widget build(BuildContext context, WidgetRef ref) => FutureBuilder(
    future: ref.read(apiClientProvider).authHeaders(),
    builder: (context, snapshot) {
      if (!snapshot.hasData) {
        return const Center(child: CircularProgressIndicator(strokeWidth: 2));
      }

      return ClipRRect(
        borderRadius: borderRadius,
        child: CachedNetworkImage(
          imageUrl: url,
          cacheKey: '${snapshot.data!['X-Organization-ID'] ?? 'none'}:$url',
          httpHeaders: snapshot.data!,
          fit: fit,
          placeholder: (_, _) => const ColoredBox(
            color: ThroughlineColors.graphiteRaised,
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          ),
          errorWidget: (_, _, _) => const ColoredBox(
            color: ThroughlineColors.graphiteRaised,
            child: Center(
              child: Icon(
                Icons.broken_image_outlined,
                color: ThroughlineColors.muted,
              ),
            ),
          ),
        ),
      );
    },
  );
}
